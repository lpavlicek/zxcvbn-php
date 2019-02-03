<?php

namespace ZxcvbnPhp;

/**
 * The main entry point.
 *
 * @see  zxcvbn/src/main.coffee
 */
class Zxcvbn
{
    /**
     * @var
     */
    protected $matcher;

    /**
     * @var
     */
    protected $scorer;

    /**
     * @var
     */
    protected $timeEstimator;

    /**
     * @var
     */
    protected $feedback;

    /**
     * @var
     */
    protected $translations = NULL;

    public function __construct()
    {
        $this->matcher = new \ZxcvbnPhp\Matcher();
        $this->scorer = new \ZxcvbnPhp\Scorer();
        $this->timeEstimator = new \ZxcvbnPhp\TimeEstimator();
        $this->feedback = new \ZxcvbnPhp\Feedback();
    }

    /**
     * Calculate password strength via non-overlapping minimum entropy patterns.
     *
     * @param string $password   Password to measure
     * @param array  $userInputs Optional user inputs
     *
     * @return array Strength result array with keys:
     *               password
     *               entropy
     *               match_sequence
     *               score
     */
    public function passwordStrength($password, array $userInputs = [])
    {
        $timeStart = microtime(true);

        $sanitizedInputs = array_map(
            function ($input) {
                return mb_strtolower((string) $input);
            },
            $userInputs
        );

        $sanitizedPassword = mb_substr($password,0,64,'UTF-8');

        // Get matches for $password.
        // Although the coffeescript upstream sets $sanitizedInputs as a property,
        // doing this immutably makes more sense and is a bit easier
        $matches = $this->matcher->getMatches($sanitizedPassword, $sanitizedInputs);

        $result = $this->scorer->getMostGuessableMatchSequence($sanitizedPassword, $matches);
        $attackTimes = $this->timeEstimator->estimateAttackTimes($result['guesses']);
        $feedback = $this->localize_feedback($this->feedback->getFeedback($attackTimes['score'], $result['sequence']));

        return array_merge(
            $result,
            $attackTimes,
            [
                'feedback'  => $feedback,
                'calc_time' => microtime(true) - $timeStart
            ]
        );
    }

    /**
     * Set the language for the feedback.
     * Translations files are in lang subdirectory.
     *
     * @param string $language  the language of the feedback
     */
    public function setFeedbackLanguage($language) {
        $lang_file = dirname(__FILE__) . '/lang/' . $language . '.json'; 
        if (file_exists($lang_file)) {
            $lang_file_content = file_get_contents($lang_file);
            $this->translations = json_decode($lang_file_content, true);
        }
    }

    private function translate($phrase) {
        $translation=NULL;
        if (! empty($this->translations)) {
            $translation = $this->translations[$phrase];
        }
        if (empty($translation)) {
            return $phrase;
        }
        else {
            return $translation;
        }
    }

    private function localize_feedback(array $feedback) {
        if (empty($this->translations)) {
            return $feedback;
        }
        if (!empty($feedback['warning'])) {
            $feedback['warning'] = $this->translate($feedback['warning']);
        }
        $suggestions = [];
        for($i = 0; $i < count($feedback['suggestions']); $i++) {

            array_push($suggestions, $this->translate($feedback['suggestions'][$i]));
        }
        $feedback['suggestions'] = $suggestions;
        return $feedback;
    }
}

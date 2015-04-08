<?php
/**
 * @link https://github.com/alextar/yii2-firephp-query-profiler.git
 * @author Alex Tarasenko
 * @license MIT
 */
namespace alextar\log;

use Yii;
use yii\log\Logger;
use yii\log\Target;

ob_start();
/**
 *
 */
class FirePHPTarget extends Target
{

    public $profile = true;
    public $explain = false;
    public $categories = ['yii\db\Command::query', 'yii\db\Command::execute'];
    protected $_timings;
    protected $_fp;

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        $this->_fp = \FirePHP::getInstance(true);
        parent::init();
    }

    /**
     * Processes the given log messages.
     * This method will filter the given messages with [[levels]] and [[categories]].
     * And if requested, it will also export the filtering result to specific medium (e.g. email).
     * @param array $messages log messages to be processed. See [[Logger::messages]] for the structure
     * of each message.
     * @param boolean $final whether this method is called at the end of the current application
     */
    public function collect($messages, $final)
    {
        if ($this->profile) {
            $this->messages = array_merge($this->messages, $this->filterMessages($messages, $this->getLevels(), $this->categories, $this->except));
            $count = count($this->messages);
            if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
                // set exportInterval to 0 to avoid triggering export again while exporting
                $oldExportInterval = $this->exportInterval;
                $this->exportInterval = 0;
                $this->export();
                $this->exportInterval = $oldExportInterval;

                $this->messages = [];
            }
        }
    }

    public function getSummary($messages)
    {
        $timings = $this->calculateTimings($messages);
        $queryCount = count($timings);
        $queryTime = $this->getTotalQueryTime($timings);


        $this->_fp->table('Query summary', array(
            array('Count','Time', 'Timings'),
            array($queryCount, $queryTime, $timings,)
        ));
    }

    /**
     * Calculates given request profile timings.
     *
     * @return array timings [token, category, timestamp, traces, nesting level, elapsed time]
     */
    protected function calculateTimings($messages)
    {
        if ($this->_timings === null) {
            $this->_timings = Yii::getLogger()->calculateTimings($messages);
        }

        return $this->_timings;
    }

    /**
     * Returns total query time.
     *
     * @param array $timings
     * @return integer total time
     */
    protected function getTotalQueryTime($timings)
    {
        $queryTime = 0;

        foreach ($timings as $timing) {
            $queryTime += $timing['duration'];
        }

        return $queryTime;
    }

    /**
     * Writes log messages to FirePHP.
     */
    public function export()
    {

        try {
            $this->getSummary($this->messages);
            foreach ($this->messages as $key => $message) {

                switch ($message[1]) {
                    case Logger::LEVEL_PROFILE_END:
                        $command = preg_replace('/\s+/', ' ', trim($message[0]));
                        $this->_fp->table(strtoupper($command), [['Time', 'Log info'], [$message[3] - $this->messages[$key - 1][3], $message]] );
                        if($this->explain && preg_match("/(SELECT|UPDATE|DELETE|INSERT)/i", $command)) {
                            $command = 'EXPLAIN ' . $command;
                            $data = Yii::$app->db->createCommand($command)->queryAll();
                            array_unshift($data, array_keys($data[0]));
                            $this->_fp->table($command, $data);
                        }
                        break;
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}
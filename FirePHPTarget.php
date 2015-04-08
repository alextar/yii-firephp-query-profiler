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
        $queries = [['SQL Statement','Time']];

        try {
            $this->getSummary($this->messages);
            foreach ($this->messages as $key => $message) {

                switch ($message[1]) {
//                    case Logger::LEVEL_ERROR:
//                        $firephp->error($message[0], $message[2]);
//                        break;
//                    case Logger::LEVEL_WARNING:
//                        $firephp->warn($message[0], $message[2]);
//                        break;
//                    case Logger::LEVEL_INFO:
//                        $firephp->info($message[0], $message[2]);
//                        break;
//                    case Logger::LEVEL_TRACE:
//                        $firephp->log($message[0]);
//                        break;
//                    default:
//                        $firephp->log($message[0], $message[2]);
//                        break;
//                    case Logger::LEVEL_PROFILE:
//                        $firephp->log($message[0], $message[2]);
//                        break;
//                    case Logger::LEVEL_PROFILE_BEGIN:
//                        $firephp->log($message[0], $message[2]);
//                        break;
                    case Logger::LEVEL_PROFILE_END:
                        $queries[] = [preg_replace('/\s+/', ' ', trim($message[0])), $message[3] - $this->messages[$key - 1][3]];
                        break;
                }
            }
                $this->_fp->table('All queries',$queries);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}
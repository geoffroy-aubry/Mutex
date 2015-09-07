<?php

namespace GAubry\Mutex;

use GAubry\Helpers\Helpers;
use Psr\Log\LoggerInterface;

/**
 * Counting semaphore.
 *
 * Structure of semaphore's lock file:
 *     "<last-update-date(Y-m-d H:i:s.cs)>|<nb-remaining-resources>|<comma-separated-pids-in-semaphore>"
 * Example:
 *     "2013-11-21 13:17:25.864069|5|12345,12346,12347"
 *
 * @author Geoffroy AUBRY <geoffroy.aubry@free.fr>
 */
class Semaphore
{
    /**
     * PSR-3 logger.
     * @var \Psr\Log\LoggerInterface
     */
    private $oLogger;

    /**
     * Milliseconds between retries to acquire lock.
     * @var int
     */
    private $iRetryDelaying;

    /**
     * Mutex needed to get or set number of available resources in semaphore.
     * @var Mutex
     */
    private $oMutex;

    /**
     * Name of resource handled by this semaphore.
     * @var string
     */
    private $sResourceName;

    /**
     * Number of initial available resources in semaphore.
     * @var int
     */
    private $iNbResources;

    /**
     * Total waiting time in seconds to acquire locks,
     * either to acquire mutex or wait an available resource.
     *
     * @var float
     */
    private $fSumWaitingTime;

    /**
     * Constructor.
     *
     * @param LoggerInterface $oLogger PSR-3 logger
     * @param int Number of initial available resources in semaphore.
     * @param int $iRetryDelaying Milliseconds between retries to acquire lock.
     * @param string $sLockPath Path to lock file
     * @param string $sResourceName Name of resource handled by this semaphore. If empty, then set to $sLockPath.
     */
    public function __construct (
        LoggerInterface $oLogger,
        $iNbResources,
        $iRetryDelaying,
        $sLockPath,
        $sResourceName = ''
    ) {
        $this->oMutex = new Mutex(
            $oLogger,
            $iRetryDelaying,
            $sLockPath,
            $sResourceName
        );
        $this->oLogger = $oLogger;
        $this->iNbResources = $iNbResources;
        $this->iRetryDelaying = $iRetryDelaying;
        $this->sResourceName = $sResourceName ?: $sLockPath;
        $this->fSumWaitingTime = 0;
        $this->clean();
    }

    /**
     * Return total waiting time in seconds to acquire locks and to get a resource.
     *
     * @return float Total waiting time in seconds to acquire locks.
     */
    public function getTotalWaitingTime ()
    {
        return $this->fSumWaitingTime;
    }

    /**
     * On Linux, remove finished pid from lock file which have forgotten to call release() method.
     * Truncate file if no remaining pid in semaphore.
     *
     * Based on /proc/<pid> filesystem. Do nothing if /proc not found.
     *
     * Lock file content:
     *     "<last-update-date(Y-m-d H:i:s.cs)>|<nb-remaining-resources>|<comma-separated-pids-in-semaphore>"
     * Example:
     *     "2013-11-21 13:17:25.864069|5|12345,12346,12347"
     */
    private function clean ()
    {
        if (file_exists('/proc')) {
            list($hLock, $sElapsedTime) = $this->oMutex->acquire();
            $this->fSumWaitingTime += $sElapsedTime;

            list(, $iCounter, $aPids) = $this->getContent($hLock);
            foreach ($aPids as $idx => $iPid) {
                if (! file_exists("/proc/$iPid")) {
                    unset($aPids[$idx]);
                    $iCounter++;
                }
            }
            if (empty($aPids)) {
                rewind($hLock);
                ftruncate($hLock, 0);
            } else {
                $this->setContent($hLock, $iCounter, $aPids, true);
            }
            $this->oMutex->release();
        }
    }

    /**
     * Retrieve content of lock file, or return "1970-01-01 00:00:00.00|$this->iNbResources|" if empty.
     * For race conditions, $this->oMutex->acquire() must be called before.
     *
     * Lock file structure:
     *     "<last-update-date(Y-m-d H:i:s.cs)>|<nb-remaining-resources>|<comma-separated-pids-in-semaphore>"
     * Example:
     *     "2013-11-21 13:17:25.864069|5|12345,12346,12347"
     *
     * @param resource $hLock Handle resource
     * @return array array(
     *     (int)   last updated date as timestamp,
     *     (int)   nb of remaining resources,
     *     (array) list of PIDs in semaphore
     * )
     */
    private function getContent ($hLock)
    {
        $sContent = trim(fgets($hLock, 1024)) ?: "1970-01-01 00:00:00.00|$this->iNbResources|";
        list($sLastUpdateDate, $iCounter, $sPids) = explode('|', $sContent);
        $fLastUpdateTs = Helpers::dateTimeToTimestamp($sLastUpdateDate);
        $aPids = array_filter(explode(',', $sPids));
        return array($fLastUpdateTs, $iCounter, $aPids);
    }

    /**
     * Update content of lock file.
     * For race conditions, $this->oMutex->acquire() must be called before.
     *
     * Lock file structure:
     *     "<last-update-date(Y-m-d H:i:s.cs)>|<nb-remaining-resources>|<comma-separated-pids-in-semaphore>"
     * Example:
     *     "2013-11-21 13:17:25.864069|5|12345,12346,12347"
     *
     * @param resource $hLock Handle resource
     * @param int      $iCounter nb of remaining resources
     * @param array    $aPids list of PIDs in semaphore
     * @param bool     $bReleasing TRUE iff we are releasing a resource
     */
    private function setContent ($hLock, $iCounter, array $aPids, $bReleasing)
    {
        $sNow = Helpers::getCurrentTimeWithCS('Y-m-d H:i:s.%s');
        if ($bReleasing) {
            $aPids = array_diff($aPids, array(getmypid()));
        } else {
            $aPids[] = getmypid();
        }
        $sContent = "$sNow|$iCounter|" . implode(',', array_unique($aPids));

        rewind($hLock);
        fwrite($hLock, $sContent);
        fflush($hLock);
        ftruncate($hLock, ftell($hLock));
    }

    /**
     * Acquire one of the semaphore's resources and return a pair <last update time, waiting time>,
     * where <last update time> is the timestamp of the last call to setContent()
     * and where <waiting time> is the elapsed time to acquire the mutex lock + to acquire a resource.
     *
     * @throws \BadMethodCallException iff lock already acquired
     * @return array Pair <(float)$fLastUpdateTs, (float)$fWaitingTime>
     */
    public function acquire ()
    {
        $bWaitMsgDisplayed = false;
        $bAcquired = false;
        $fStartDisplayingWait = 0;
        $fWaitingTime = 0;

        do {
            list($hLock, $fMutexWaitingTime) = $this->oMutex->acquire();
            $fWaitingTime += $fMutexWaitingTime;

            list($fLastUpdateTs, $iCounter, $aPids) = $this->getContent($hLock);
            if ($iCounter > 0) {
                $bAcquired = true;
                $this->setContent($hLock, --$iCounter, $aPids, false);
            }
            $this->oMutex->release();

            if (! $bAcquired) {
                if (! $bWaitMsgDisplayed) {
                    $bWaitMsgDisplayed = true;
                    $fStartDisplayingWait = microtime(true);
                    $this->oLogger->info(
                        'Waiting to acquire lock on {resource}…',
                        array('resource' => $this->sResourceName)
                    );
                }
                usleep(1000 * (rand(0, $this->iRetryDelaying) + round($this->iRetryDelaying/2)));
            }
        } while (! $bAcquired);

        if ($bWaitMsgDisplayed) {
            $fSemElapsedTime = microtime(true) - $fStartDisplayingWait;
            $fWaitingTime += $fSemElapsedTime;
            $this->oLogger->info(
                'Lock acquired after {elapsed_time}s',
                array('elapsed_time' => Helpers::round($fSemElapsedTime, 2))
            );
        }

        $this->fSumWaitingTime += $fWaitingTime;
        return array($fLastUpdateTs, $fWaitingTime);
    }

    /**
     * Release one of the semaphore's resources and return elapsed time to acquire mutex lock.
     *
     * @return float Waiting time to acquire mutex lock.
     */
    public function release ()
    {
        list($hLock, $fElapsedTime) = $this->oMutex->acquire();
        $this->fSumWaitingTime += $fElapsedTime;
        list(, $iCounter, $aPids) = $this->getContent($hLock);
        $this->setContent($hLock, ++$iCounter, $aPids, true);
        $this->oMutex->release();
        return $fElapsedTime;
    }
}

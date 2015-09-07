<?php

namespace GAubry\Mutex;

use GAubry\Helpers\Helpers;
use Psr\Log\LoggerInterface;

/**
 * Mutex.
 *
 * @author Geoffroy AUBRY <geoffroy.aubry@free.fr>
 */
class Mutex
{
    /**
     * PSR-3 Logger.
     * @var \Psr\Log\LoggerInterface
     */
    private $oLogger;

    /**
     * Milliseconds between retries to acquire lock.
     * @var int
     */
    private $iRetryDelaying;

    /**
     * Path to lock file.
     * @var string
     * @see $hlock
     */
    private $sLockPath;

    /**
     * Name of resource handled by this mutex.
     * @var string
     */
    private $sResourceName;

    /**
     * Handle resource.
     * @var resource
     * @see $sLockPath
     */
    private $hLock;

    /**
     * Total waiting time in seconds to acquire locks.
     * @var float
     */
    private $fSumWaitingTime;

    /**
     * Constructor.
     *
     * @param LoggerInterface $oLogger PSR-3 logger
     * @param int $iRetryDelaying Milliseconds between retries to acquire lock.
     * @param string $sLockPath Path to lock file
     * @param string $sResourceName Name of resource handled by this mutex. If empty, then set to $sLockPath.
     */
    public function __construct (LoggerInterface $oLogger, $iRetryDelaying, $sLockPath, $sResourceName = '')
    {
        $this->oLogger = $oLogger;
        $this->iRetryDelaying = $iRetryDelaying;
        $this->sLockPath = $sLockPath;
        $this->sResourceName = $sResourceName ?: $sLockPath;
        $this->hLock = null;
        $this->fSumWaitingTime = 0;
    }

    /**
     * Acquire lock on resource and return a pair <resource, waiting time>,
     * where waiting time is the elapsed time to acquire the lock.
     *
     * @throws \BadMethodCallException if lock already acquired
     * @return array Pair <(resource)$hLock, (float)$fWaitingTime>
     */
    public function acquire ()
    {
        if ($this->hLock !== null) {
            throw new \BadMethodCallException('You must call release() method before!');
        }

        $this->hLock = fopen($this->sLockPath, 'c+');
        $bWaitMsgDisplayed = false;
        $fStartDisplayingWait = 0;
        while (! flock($this->hLock, LOCK_EX | LOCK_NB)) {
            if (! $bWaitMsgDisplayed) {
                $bWaitMsgDisplayed = true;
                $fStartDisplayingWait = microtime(true);
                $this->oLogger->info(
                    'Waiting to acquire Mutex lock on {resource}…',
                    array('resource' => $this->sResourceName)
                );
            }
            usleep(1000 * (rand(0, $this->iRetryDelaying) + round($this->iRetryDelaying/2)));
        }

        if ($bWaitMsgDisplayed) {
            $fWaitingTime = microtime(true) - $fStartDisplayingWait;
            $this->fSumWaitingTime += $fWaitingTime;
            $this->oLogger->info(
                'Mutex lock acquired after {elapsed_time}s',
                array('elapsed_time' => Helpers::round($fWaitingTime, 2))
            );
        } else {
            $fWaitingTime = 0;
        }
        return array($this->hLock, $fWaitingTime);
    }

    /**
     * Release the lock on resource.
     *
     * @throws \BadMethodCallException iff no lock was acquired
     */
    public function release ()
    {
        if ($this->hLock === null) {
            throw new \BadMethodCallException('You must call acquire() method before!');
        }
        flock($this->hLock, LOCK_UN);
        fclose($this->hLock);
        $this->hLock = null;
    }

    /**
     * Return total waiting time in seconds to acquire locks.
     *
     * @codeCoverageIgnore
     * @return float Total waiting time in seconds to acquire locks.
     */
    public function getTotalWaitingTime ()
    {
        return $this->fSumWaitingTime;
    }
}

<?php
namespace ngyuki\Tests;

declare(ticks = 1);

/**
 * @author ngyuki
 */
class ProcessFork
{
    /**
     * 子プロセスのPID
     *
     * @var int
     */
    private $_pid;

    /**
     * デストラクタ
     */
    public function __destruct()
    {
        $this->term();
    }

    /**
     * サーバの実行
     *
     * @throws Exception
     */
    public function fork($action)
    {
        ASSERT('$this->_pid === null');

        $pid = pcntl_fork();

        if ($pid == -1)
        {
            throw new \RuntimeException("pcntl_fork(): unknown error");
        }

        if ($pid)
        {
            $this->_pid = $pid;
        }
        else
        {
            $this->_pid = false;

            try
            {
                $this->_init();
                call_user_func($action);
                $this->term();
            }
            catch (\Exception $ex)
            {
                $this->_except($ex);
                $this->term();
            }
        }
    }

    /**
     * 子プロセスの終了
     */
    public function term()
    {
        if ($this->_pid === false)
        {
            $pid = posix_getpid();
            posix_kill($pid, SIGTERM);
            exit;
        }
        else if ($this->_pid)
        {
            $pid = $this->_pid;

            if (pcntl_waitpid($pid, $st, WNOHANG) == 0)
            {
                posix_kill($pid, SIGTERM);
                $ret = pcntl_waitpid($pid, $st);
            }

            $this->_pid = null;
        }
    }

    /**
     * 子プロセスの初期化
     */
    private function _init()
    {
        $self = $this;

        pcntl_signal(SIGTERM, SIG_DFL);

        // 出力をすべて破棄
        ob_start(function(){});

        // PHPエラーで終了
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        // 未処理の例外で終了
        set_exception_handler(function ($ex) use ($self) {
            $self->term();
        });

        // 最大生存期間
        $lifelimit = microtime(true) + 600;

        // 生存期間が過ぎたら終了
        register_tick_function(function () use ($self, $lifelimit) {
            if (microtime(true) >= $lifelimit)
            {
                $self->term();
            }
        });
    }

    private function _except(\Exception $ex)
    {
        $display = ini_get('display_errors');

        if ($display)
        {
            $stream = null;

            if (strtolower($display) === 'stderr')
            {
                if (defined('STDERR') && is_resource(STDERR))
                {
                    $stream = STDERR;
                }
            }

            if ($stream === null)
            {
                if (defined('STDOUT') && is_resource(STDOUT))
                {
                    $stream = STDOUT;
                }
            }

            if ($stream === null)
            {
                echo $ex;
            }
            else
            {
                fputs($stream, $ex);
            }
        }

        $log = ini_get('log_errors');

        if ($log)
        {
            error_log($ex);
        }
    }
}

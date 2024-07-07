<?php

namespace RapTToR;

/**
 *  A Threads class
 *
 *  Use parallel processing with PHP
 *  Simple as it could be.
 *
 *  @author rapttor
 */

defined("THREAD_DEBUG") || define("THREAD_DEBUG", true);

class Threads
{
   protected const READ_BUFFER = 1024 * 4;
   protected $process;
   protected $callback;
   protected $child;
   protected $parent;
   protected $debug = true;

   public function __construct($options, $default = array(
      "process" => false,
      "debug" => true
   ))
   {
      extract($options = self::mergeOptions($options, $default));
      $this->debug = $debug;
      if (!defined("THREADS_SHUTDOWN_REGISTERED")) {
         // on first init
         if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
            echo "main process pid = " . posix_getpid() . PHP_EOL;

         register_shutdown_function(function () {
            if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
               echo PHP_EOL . posix_getpid() . " process finished" . PHP_EOL;
         });
         define("THREADS_SHUTDOWN_REGISTERED", true);
      }
      if (php_sapi_name() !== 'cli') {
         throw new \RuntimeException('threads are available in CLI mode only.');
      }
      pcntl_async_signals(true);
      if (is_callable($process))
         $this->process = \Closure::bind($process, $this, static::class);
   }

   protected static function mergeOptions($options, $default = false)
   {
      if (!is_array($options))
         $options = array();
      if (!is_array($default))
         $default = array();
      foreach ($default as $k => $v)
         if (!isset($options[$k]))
            $options[$k] = $v;
      return $options;
   }

   protected function respond(string $value): void
   {
      $write = [$this->parent];
      if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
         echo "writing... " . PHP_EOL;

      if (stream_select($read, $write, $except, 1)) {
         $socket = reset($write);
         flock($socket, LOCK_EX);
         fwrite($socket, $value);
         flock($socket, LOCK_UN);
         if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
            echo "done" . PHP_EOL;
         posix_kill(posix_getppid(), SIGCHLD);
      }
      // echo "\n";
   }

   protected function receive(): void
   {
      if (!$this->callback) {
         return;
      }
      if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
         echo "receiving... " . PHP_EOL;

      $read = [$this->child];

      if (stream_select($read, $write, $except, null)) {
         $socket = reset($read);
         flock($socket, LOCK_EX);
         $response = '';
         do {
            $buffer = fread($socket, static::READ_BUFFER);
            $response .= $buffer;
         } while (strlen($buffer) == static::READ_BUFFER);

         flock($socket, LOCK_UN);
         if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
            echo "done" . PHP_EOL;
         call_user_func($this->callback, $response);
      } else {
         if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
            echo "cannot read" . PHP_EOL;
         error_log("cannot read return data.");
      }
   }

   public function synchronize($options, $defaults = array("callback" => false)): self
   {
      extract($options = \RapTToR\Threads::mergeOptions($options, $defaults));
      if (is_callable($callback))
         $this->callback = $callback;
      return $this;
   }

   public function start(): void
   {
      $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
      $pid = pcntl_fork();
      if ($pid == -1) {
         throw new \RuntimeException('Cannot fork process');
      } elseif ($pid) {
         if ($this->debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
            echo 'starting process:', $pid, PHP_EOL;
         // parent
         fclose($pair[0]);
         $this->child = $pair[1];

         pcntl_signal(SIGCHLD, function () {
            $this->receive();
         });
      } else {
         fclose($pair[1]);
         $this->parent = $pair[0];
         call_user_func($this->process);
         exit(0);
      }
   }

   public static function run(
      $options,
      $defaults = array(
         "process" => false,
         "response" => false,
         "debug" => true
      )
   ) {
      extract($options = \RapTToR\Threads::mergeOptions($options, $defaults));
      if (is_callable($process)) {
         if (!$response || !is_callable($response)) {
            (new \RapTToR\Threads(array("process" => $process, "debug" => $debug)))->start();
         } else {
            (new \RapTToR\Threads(array("process" => $process, "debug" => $debug)))->synchronize(array("response" => $response))->start();
         }
      } else {
         $message = "Cannot start process " . json_encode($options);
         if ($debug || (defined("THREAD_DEBUG") && THREAD_DEBUG))
            echo $message;
         error_log($message, E_USER_ERROR);
      }
   }
}

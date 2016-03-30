<?php

namespace Ampersand;

class Logger {
    
    /**
     * Contains all instantiated loggers
     * @var \Monolog\Logger[]
     */
    private static $loggers = array();
    
    /**
     * Contains list of handlers that are added to a logger when it is instantiated
     * @var \Monolog\Handler\AbstractHandler[]
     */
    private static $genericHandlers = array();
    
    /**
     * Associative array containing array with handlers for specific channels
     * @var array
     */
    private static $channelHandlers = array();
    
    /**
     * 
     * @param string $channel
     * @return \Monolog\Logger
     */
    public static function getLogger($channel){
        
        if(isset(self::$loggers[$channel])) return self::$loggers[$channel];
        else { 
            $logger = new \Monolog\Logger($channel);
            
            // Add generic handlers (i.e. for all channels)
            foreach(self::$genericHandlers as $handler) $logger->pushHandler($handler); 
            
            // Add handlers for specific channels
            foreach((array)self::$channelHandlers[$channel] as $handler) $logger->pushHandler($handler);
            
            self::$loggers[$channel] = $logger;
            
            return $logger;
        }
    }
    
    /**
     * 
     * @return \Monolog\Logger
     */
    public static function getUserLogger(){
        return \Ampersand\Logger::getLogger('USERLOG');
    }
    
    /**
     * Register a handler that is added when certain logger (channel) is instantiated
     * @param string $channel
     * @param \Monolog\Handler $handler
     */
    public static function registerHandlerForChannel($channel, $handler){
        self::$channelHandlers[$channel][] = $handler;        
    }
    
    /**
     * Register a handler that is added to all loggers when instantiated
     * @param \Monolog\Handler $handler
     */
    public static function registerGenericHandler($handler){
        self::$genericHandlers[] = $handler;
    }
    
}

class NotificationHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    protected function write(array $record){
        \Notifications::addNotification($record['level'], $record['formatted']);
    }
}

?>
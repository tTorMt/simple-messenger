<?php

namespace tTorMt\SChat\Storage;

/**
 * MySqlHandler generator
 */
class MySqlHandlerGenerator implements DBHandlerGenerator
{
    /**
     * Generate MySqlHandler
     * @return DBHandler
     */
    public function getDBHandler(): DBHandler
    {
        return new MySqlHandler();
    }
}

<?php

namespace tTorMt\SChat\Storage;

/**
 * DBHandler object generator interface
 */
interface DBHandlerGenerator
{
    /**
     * Returns a new DBHandler object
     * @return DBHandler
     */
    public function getDBHandler(): DBHandler;
}

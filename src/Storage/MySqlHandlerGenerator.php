<?php

namespace tTorMt\SChat\Storage;

class MySqlHandlerGenerator implements DBHandlerGenerator
{
    public function getDBHandler(): DBHandler
    {
        return new MySqlHandler();
    }
}

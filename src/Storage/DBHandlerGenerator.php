<?php

namespace tTorMt\SChat\Storage;

interface DBHandlerGenerator
{
    public function getDBHandler(): DBHandler;
}

<?php

trait CompareTrait
{

    protected $compareCache = [];

    /**
     * @param string $method
     * @param string $prop
     * @return mixed
     */
    private function getCompareValue($method, $prop)
    {
        if (!isset($this->compareCache[$method])) {
            $this->compareCache[$method] = json_decode(file_get_contents(__DIR__ . "/compare/$method.json"), true);
        }
        return $this->compareCache[$method][$prop];
    }

    /**
     * @param string $method
     * @param string $prop
     * @return array
     */
    private function getCompareArray($method, $prop)
    {
        $r = [];
        $str = $this->getCompareValue($method, $prop);
        $str = str_replace('Enjoin::', '\\Enjoin\\Enjoin::', $str);
        eval('$r = ' . $str . ';');
        return $r;
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function getCompareSql($method)
    {
        return $this->getCompareValue($method, getenv('ENJ_DIALECT'));
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function getCompareParams($method)
    {
        return $this->getCompareArray($method, 'params');
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function getCompareCollection($method)
    {
        return $this->getCompareArray($method, 'collection');
    }

}

<?php

use Enjoin\Enjoin;

class CompareQueries
{

    private $queryA = '';
    private $queryB = '';

    /**
     * CompareQueries constructor.
     * @param $queryA
     * @param $queryB
     */
    public function __construct($queryA, $queryB)
    {
        $this->queryA = $queryA;
        $this->queryB = $queryB;
    }

    /**
     * @param string $queryA
     * @param string $queryB
     * @return bool
     */
    public static function isSame($queryA, $queryB)
    {
        return (new CompareQueries($queryA, $queryB))->isSameHandler();
    }

    public function isSameHandler()
    {
        if ($this->queryA !== $this->queryB) {
            list($isSame, $queryA, $queryB) = $this->dropSpaces($this->queryA, $this->queryB);
            if (!$isSame) {
                list($isSame, $queryA, $queryB) = $this->sortSelect($queryA, $queryB);
                if (!$isSame) {
                    list($isSame, $queryA, $queryB) = $this->sortJunctionClause($queryA, $queryB);
                    return $isSame;
                }
            }
        }
        return true;
    }

    /**
     * @param string $queryA
     * @param string $queryB
     * @return array
     */
    private function dropSpaces($queryA, $queryB)
    {
        $queryA = str_replace(' ', '', $queryA);
        $queryB = str_replace(' ', '', $queryB);
        return [$queryA === $queryB, $queryA, $queryB];
    }

    /**
     * @param string $queryA
     * @param string $queryB
     * @return array
     */
    private function sortSelect($queryA, $queryB)
    {
        $pattern = '/SELECT(.+)FROM/U';
        foreach (['A' => $queryA, 'B' => $queryB] as $key => $query) {
            $r = preg_replace_callback($pattern, function ($matches) {
                $list = explode(',', $matches[1]);
                sort($list);
                return join(',', $list);
            }, $query);
            $link = "query$key";
            $$link = $r;
        }
        return [$queryA === $queryB, $queryA, $queryB];
    }

    /**
     * @param string $queryA
     * @param string $queryB
     * @return array
     */
    private function sortJunctionClause($queryA, $queryB)
    {
        $pattern = '/WHERE\(?(`\w+`\.`\w+`=`\w+`.`id`|`\w+`\.`id`=`\w+`.`\w+`)/U';
        foreach (['A' => $queryA, 'B' => $queryB] as $key => $query) {
            $r = preg_replace_callback($pattern, function ($matches) {
                $list = explode('=', $matches[1]);
                sort($list);
                return join('=', $list);
            }, $query);
            $link = "query$key";
            $$link = $r;
        }
        return [$queryA === $queryB, $queryA, $queryB];
    }

}

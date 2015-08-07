<?php

class maxPager {
    /**
     * @var string
     */
    private $name;
    /**
     * @var integer
    */
    private $limit;
    
    /* @var integer
    */
    private $page = 0;
    
    public $query = array();
    
    function __construct($name,$limit) {
        $this->name = $name;
        $this->limit = $limit;
    }
    /*задаёт текущую страницу*/
     public function setPage($page = 1) {
        $page = is_numeric($page)? $page : 1;
        $this->page = $page;
     }
     /*задаёт текущую страницу _end*/
     
      public function setShow($page) {
        $page = is_numeric($page)? $page : 10;
        $this->show = $page;
     }
     
     /*задаёт выборку*/
     public function setQuery($query) {
        $this->query = $query;
     }
     /*задаёт выборку _end**/
     
    /*возвращает количество элементов******************/
    public function count() {
        if(isset($this->query) && count($this->query) > 0){
            return count($this->query);
        }
        else{
            return 0;
        }
    }
    /*возвращает количество элементов _end********************/
    
    /*возвращает элементы пагинатора******************/
    public function getLinks($count_show_pages = 10) {
        if(isset($this->query) && count($this->query) > 1){
          $count_pages = $this->count();
          $active = $this->page;
          $left = $active - 1;
          $right = $count_pages - $active;
          $length = ceil($this->count()/$this->limit);
            if ($left < floor($count_show_pages / 2)){
                $start = 1;
            }
            else{
                $start = $active - floor($count_show_pages / 2);
            }
            $end = $start + $count_show_pages - 1;
            if ($end > $count_pages) {
              $start -= ($end - $count_pages);
              $end = $length;
              if ($start < 1) $start = 1;
            }
            else{
                if($end > $length){
                    $end = $length;
                    $start = $end - $count_show_pages;
                    if($start < 1)$start = 1;
                }
            }
          $range_array = range($start,$end);
          return $range_array;
        }
        else{
           return false;
        }
    }
    /*возвращает элементы пагинатора _end****************/
    
    //последняя страница
    public function getLastPage() {
        $last = ceil($this->count()/$this->limit);
        return $last;
    }
    //последняя страница_end
    /*вывод текущей страницы*/
    public function getCurrent() {
        return $this->page;
    }
    /*вывод текущей страницы _end*/
    
    public function getElements() {
        $start = ($this->getCurrent()-1)*$this->limit;
        $elements = array_slice($this->query, $start, $this->limit, true); ;
        return $elements;
    }
}
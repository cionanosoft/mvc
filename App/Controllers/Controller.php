<?php

namespace App\Controller;

class Controller {

    protected $View;
    protected $parameter = [];
    public function __construct($view, $parameters) {
        $this->View = $view;
        $this->parameter = $parameters;
        echo 'entro';
        
    }
    
    public function getView() {
        return $this->View;
    }
    
    public function getParameters() {
        return $this->parameter;
    }
    
} 
<?php

class HomeController {

    public function indexAction($urlParams, $postParams) {
        
        $is_template = false;

        return new View('home', compact('is_template'));
    }
    
}

<?php
namespace TheFold\GraphQLWP;

$dir = '/';
for($i=0; $i<=5; $i++, $dir.='../') {
    
    //Look for vendor path, it'll be in here or in a parent dir
    $vendor_autoload = __DIR__.$dir.'vendor/autoload.php';
    if(file_exists($vendor_autoload)){
        require_once $vendor_autoload;
        break;
    }
}

spl_autoload_register(function($classname) {

    if(preg_match('#^TheFold\\\GraphQLWP\\\#',$classname)){
        
        $path = preg_replace(['#^TheFold\\\GraphQLWP#','#\\\#'],[__DIR__,'/'],$classname).'.php';

        require $path; 
    }
});

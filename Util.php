<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 4/13/17
 * Time: 3:31 PM
 */

namespace Ucsf\RestOrmBundle;

use GuzzleHttp\Exception\BadResponseException;

class Util {
    public static function getGuzzleExceptionBody(BadResponseException $exception) {
            return json_decode($exception->getResponse()->getBody()->getContents());
    }

    public static function twigRender($templateString = null, $variables = null) {
        if (!$templateString) {
            return FALSE;
        }
        if (!$variables) {
            return $templateString;
        }

        $twig = new \Twig_Environment(new \Twig_Loader_Array());
        $template = $twig->createTemplate($templateString);
        return $template->render($variables);
    }
    
}
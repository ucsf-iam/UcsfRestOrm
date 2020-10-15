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

    public static function twigRender($templateString = '', $variables = []) {
        $loader = new \Twig\Loader\ArrayLoader([
            'template.html' => $templateString,
        ]);
        $twig = new \Twig\Environment($loader);

        return $twig->render('template.html', $variables);
    }
    
}
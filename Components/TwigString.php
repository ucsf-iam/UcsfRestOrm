<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 3/4/16
 * Time: 11:30 PM
 */

namespace IAM\RestOrmBundle\Components;

/**
 * Class TwigString
 *
 * A component of standalone Twig utilities
 *
 * @package IAM\RestOrmBundle\Components
 */
class TwigString implements \Twig_LoaderInterface {
    public function getSource($name)
    {
        return $name;
    }

    public function isFresh($name, $time)
    {
        return true;
    }

    public function exists($name)
    {
        return true;
    }

    public function getCacheKey($name)
    {
        return 'twigStringService:' . $name;
    }

    /**
     * Given a string with Twig variables and directives and a list of
     * key/value variable pairs, render the string as
     * @param $input
     * @param $variables
     * @return string
     */
    public function render($input, array $variables) {
        $twig = new \Twig_Environment($this);
        $output = $twig->render($input, $variables);
        return $output;
    }
}
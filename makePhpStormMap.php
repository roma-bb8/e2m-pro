<?php
// Init framework
require 'app/Mage.php';
Mage::app();

// Factory methods to search for
$methods = array(
    'Mage::helper',
    'Mage::getModel',
    'Mage::getResourceModel',
    'Mage::getSingleton',
    'Mage::getResourceSingleton',
);

// Path to search for source files
$projectPath = 'app';
$sourceRegex = '/^.+\.(?:php|phtml)$/';

// Collect class names
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectPath, FilesystemIterator::FOLLOW_SYMLINKS));
$files = new RegexIterator($iterator, $sourceRegex, RecursiveRegexIterator::GET_MATCH);
$classes = array();
foreach ($files as $file) {
    $code = @file_get_contents($file[0]);
    if ($code) {
        foreach ($methods as $method) {
            if (preg_match_all('#' . preg_quote($method) . '\s*\(\s*[\'"]([a-zA-Z0-9/_]+)[\'"]#', $code, $matches)) {
                if (empty($classes[$method])) {
                    $classes[$method] = array();
                }
                foreach ($matches[1] as $token) {
                    if (isset($classes[$method][$token])) {
                        continue;
                    }

                    try {
                        switch ($method) {
                            case 'Mage::getModel':
                            case 'Mage::getSingleton':
                                $class = Mage::getConfig()->getModelClassName($token);
                                if ($class) {
                                    $classes[$method][$token] = $class;
                                }
                                break;

                            case 'Mage::getResourceModel':
                            case 'Mage::getResourceSingleton':
                                $class = Mage::getConfig()->getResourceModelClassName($token);
                                if ($class) {
                                    $classes[$method][$token] = $class;
                                }
                                break;

                            case 'Mage::helper':
                                $class = Mage::getConfig()->getHelperClassName($token);
                                if ($class) {
                                    $classes[$method][$token] = $class;
                                }
                                break;

                            default:
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
    }
}

echo '<?php namespace PHPSTORM_META {' . PHP_EOL . PHP_EOL;
echo '    /** @noinspection PhpUnusedLocalVariableInspection */' . PHP_EOL;
echo '    /** @noinspection PhpIllegalArrayKeyTypeInspection */' . PHP_EOL;
echo '    $STATIC_METHOD_TYPES = [' . PHP_EOL;

foreach ($methods as $method) {
    echo "        \\" . $method . "('') => [" . PHP_EOL;

    foreach ($classes[$method] as $prefix => $className) {
        echo "            '" . $prefix . "' instanceof \\" . $className . ',' . PHP_EOL;
    }
    echo ' ],' . PHP_EOL;
}

echo ' ];' . PHP_EOL;
echo '}';

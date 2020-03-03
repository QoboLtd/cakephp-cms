<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cms\View;

use Cake\Core\Configure;
use DirectoryIterator;
use UnexpectedValueException;

class Shortcode
{
    /**
     * Shortcodes runner.
     *
     * @param string $content Content to search for shortcodes
     * @return string the output of the shortcodes
     */
    public static function doShortcode(string $content): string
    {
        if (! is_string($content)) {
            return '';
        }

        // Get all the shortcodes
        $shortcodes = self::get($content);

        // Loop through all shortcodes
        foreach ($shortcodes as $shortcode) {
            // Parse the shortcodes
            $parsed = self::parse($shortcode);

            // Replace the content with the shortcode output
            $content = str_replace($shortcode['full'], $parsed, $content);
        }

        // Return the content
        return $content;
    }

    /**
     * Shortcodes getter.
     *
     * @param string $content Content to look for shortcodes
     * @return mixed[]
     */
    public static function get(string $content): array
    {
        if (!is_string($content)) {
            return [];
        }

        preg_match_all('/' . self::getShortcodeRegex() . '/', $content, $matches);
        if (empty($matches[0])) {
            return [];
        }

        $result = [];
        foreach ($matches[0] as $k => $match) {
            $result[] = [
                'full' => $match,
                'name' => $matches[2][$k],
                'params' => static::getParams($match),
                'content' => $matches[5][$k],
            ];
        }

        return $result;
    }

    /**
     * Shortcode regex.
     * Source: https://developer.wordpress.org/reference/functions/get_shortcode_regex/
     *
     * @return string The Regex string
     */
    public static function getShortcodeRegex(): string
    {
        // @codingStandardsIgnoreStart
        return
            '\\['                                // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "([A-Za-z_]+)"                     // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag ...
            .     '\\]'                          // ... and closing bracket
            . '|'
            .     '\\]'                          // Closing bracket
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
        // @codingStandardsIgnoreEnd
    }

    /**
     * Shortcode parser.
     *
     * @param mixed[] $shortcode Shortcode to parse
     * @return string
     */
    public static function parse(array $shortcode): string
    {
        if (empty($shortcode)) {
            return '';
        }

        $method = 'render' . ucfirst($shortcode['name']);

        if (!method_exists(__CLASS__, $method)) {
            return isset($shortcode['content']) ? $shortcode['content'] : '';
        }

        return static::$method($shortcode['params'], $shortcode['content']);
    }

    /**
     * Shortcodes getter.
     *
     * @param string $shortcode Shortcode
     * @return mixed[]
     */
    protected static function getParams(string $shortcode): array
    {
        preg_match_all('/\s(\w+)=["|\'](.*?)["|\']/', $shortcode, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $result = [];
        foreach ($matches[1] as $k => $v) {
            $result[$v] = $matches[2][$k];
        }

        return $result;
    }

    /**
     * Render gallery shortcode.
     *
     * Note: ideally this should be moved to its own class
     * and that is the reason we are using fully namespaced
     * classes references, and variables that could be set
     * as class properties ($imageExtensions and $html).
     *
     * @param mixed[] $params Shortcode parameters
     * @return string
     */
    protected static function renderGallery(array $params): string
    {
        $imageExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        $html = [
            'wrapper' => '<div class="row">%s</div>',
            'item' => '<div class="col-xs-4 col-md-3 col-lg-2"><a href="%s" data-lightbox="gallery"><img src="%s" class="thumbnail"/></a></div>',
            'error' => '<div class="alert alert-danger" role="alert">%s</div>',
        ];

        $path = !empty($params['path']) ? $params['path'] : '';
        $path = trim($path, DIRECTORY_SEPARATOR);

        // skip if path is not defined
        if (empty($path)) {
            return sprintf($html['error'], 'Gallery has no path');
        }

        try {
            $iterator = new DirectoryIterator(WWW_ROOT . $path);
        } catch (UnexpectedValueException $e) {
            return sprintf($html['error'], 'No images found in: ' . $path);
        }

        $result = '';
        $files = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!in_array(strtolower($file->getExtension()), $imageExtensions)) {
                continue;
            }

            $options = Configure::read('TinymceElfinder.options');

            $elFinder = new \elFinder($options);
            /**
             * @var \elFinderVolumeLocalFileSystem $volume
             */
            $volume = $elFinder->getVolume('l' . $options['roots'][0]['id'] . '_');

            $hash = $volume->getHash(dirname($file->getPathname()), $file->getFilename());
            $stat = $volume->file($hash);

            $tmbname = $stat['hash'] . $stat['ts'] . '.png';

            $thumbnail = $options['roots'][0]['path'] . DS . $options['roots'][0]['tmbPath'] . DS . $tmbname;
            // generate non-existing thumbnail
            if (!file_exists($thumbnail)) {
                $volume->tmb($hash);
            }

            $image = $options['roots'][0]['URL'] . '/' . $options['roots'][0]['tmbPath'] . '/' . $tmbname;
            $link = '/' . $path . '/' . $file->getFilename();

            array_push($files, [
                'name' => $file->getFilename(),
                'image' => $image,
                'link' => $link,
            ]);
        }

        $names = array_map(
            function ($item) {
                return $item['name'];
            },
            $files
        );

        sort($names, SORT_NATURAL);

        foreach ($names as $k => $item) {
            foreach ($files as $file) {
                if ($file['name'] == $item) {
                    $result .= sprintf($html['item'], $file['link'], $file['image']);
                }
            }
        }

        $result = sprintf($html['wrapper'], $result);

        return $result;
    }
}

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

class Shortcode
{
    /**
     * Shortcodes getter.
     *
     * @param string $content Content to look for shortcodes
     * @return array
     */
    public static function get($content)
    {
        if (!is_string($content)) {
            return [];
        }

        preg_match_all('/\[([A-Za-z_]+)\s?(.*?)\]/', $content, $matches);
        if (empty($matches[0])) {
            return [];
        }

        $result = [];
        foreach ($matches[0] as $k => $match) {
            $result[] = [
                'full' => $match,
                'name' => $matches[1][$k],
                'params' => static::getParams($match)
            ];
        }

        return $result;
    }

    /**
     * Shortcode parser.
     *
     * @param array $shortcode Shortcode to parse
     * @return string
     */
    public static function parse(array $shortcode)
    {
        $result = '';

        if (empty($shortcode)) {
            return $result;
        }

        $method = 'render' . ucfirst($shortcode['name']);

        if (!method_exists(__CLASS__, $method)) {
            return $result;
        }

        return static::$method($shortcode['params']);
    }

    /**
     * Shortcodes getter.
     *
     * @param string $shortcode Shortcode
     * @return array
     */
    protected static function getParams($shortcode)
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
     * @param array $params Shortcode parameters
     * @return string
     */
    protected static function renderGallery(array $params)
    {
        $imageExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        $html = [
            'wrapper' => '<div class="row">%s</div>',
            'item' => '<div class="col-xs-4 col-md-3 col-lg-2"><a href="%s" data-lightbox="gallery"><img src="%s" class="thumbnail"/></a></div>',
            'error' => '<div class="alert alert-danger" role="alert">%s</div>'
        ];

        $path = !empty($params['path']) ? $params['path'] : '';
        $path = trim($path, DIRECTORY_SEPARATOR);

        // skip if path is not defined
        if (empty($path)) {
            return sprintf($html['error'], 'Gallery has no path');
        }

        try {
            $iterator = new \DirectoryIterator(WWW_ROOT . $path);
        } catch (\UnexpectedValueException $e) {
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

            $options = \Cake\Core\Configure::read('TinymceElfinder.options');

            $elFinder = new \elFinder($options);
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

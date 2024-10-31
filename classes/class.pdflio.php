<?php

defined('ABSPATH') or die;

use pdflio\lib\Filesystem;

class Pdflio {
	private $baseUrl = 'https://pdfl.io/convert/pdf';
	private $api_key;

	public function __construct($api_key) {
		$this->api_key = $api_key;
	}

    public function get()
    {
        $url = $this->input_get('url');
        $filename = $this->input_get('filename', 'file_name');
        $view = $this->input_get('view') ?: 1;

        $queryData = [
            'url' => $url,
            'filename' => $filename,
            'download' => 1,
            'key' => $this->api_key,
        ];

		if($size = $this->input_get('size')) {
            $queryData['sizey'] = $size;
        }
        if($no_background = $this->input_get('no_background')) {
            $queryData['no-background'] = $no_background;
        }
        if($greyscale = $this->input_get('greyscale')) {
            $queryData['greyscale'] = $greyscale;
        }
        if($format = $this->input_get('format')) {
            $queryData['format'] = $format;
        }
        if($top_view_only = $this->input_get('top_view_only')) {
            $queryData['top-view-only'] = $top_view_only;
        }
        if($disable_javascript = $this->input_get('disable_javascript')) {
            $queryData['disable-javascript'] = $disable_javascript;
        }
        if($disable_images = $this->input_get('disable_images')) {
            $queryData['disable-images'] = $disable_images;
        }
        if($just_wait = $this->input_get('just_wait')) {
            $queryData['just-wait'] = $just_wait;
        }
        if($delay = $this->input_get('delay')) {
            $queryData['delay'] = $delay;
        }

        $this->generate($queryData);
        die;
    }

    private function generate($queryData)
    {
        $filesystem = new pdflio\lib\Filesystem\Filesystem;
        $queryString = http_build_query($queryData);
        $url = $this->baseUrl . '?' . $queryString;

        $name = isset($queryData['filename']) && $queryData['filename']
                    ? $queryData['filename']
                    : 'file.pdf';

        if(strpos($name, ".pdf") === FALSE){
            $name .= ".pdf";
        }

        if(!is_dir(PDFLIO_CACHE_PATH)){
            mkdir(PDFLIO_CACHE_PATH, 0755, true);
        }

        $cacheKey = $this->generateCacheKey($queryData);
		$fullPath = PDFLIO_CACHE_PATH . '/' . $cacheKey;
        if(!($this->cacheGet($fullPath))) {
            $response = wp_remote_get($url);

            $filesystem->write($fullPath, $response['body'], true);
        }

        $output = $filesystem->read($fullPath);

        //ob_clean();
        //flush();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename='.$name);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));

        readfile($fullPath);
        exit;
    }

    private function generateCacheKey($queryData)
    {
        return md5(implode(',', $queryData));
    }

    private function input_get($var, $sanitize = 'text_field') {
        if(isset($_REQUEST[$var])) {
            switch ($sanitize) {
                case "text_field":
                    return sanitize_text_field($_REQUEST[$var]);
                    break;
                case "email":
                    return sanitize_email($_REQUEST[$var]);
                    break;
                case "file_name":
                    return sanitize_file_name($_REQUEST[$var]);
                    break;
                default:
                    return sanitize_text_field($_REQUEST[$var]);
            }
        }
        return '';
    }

    private function cacheGet($fullPath) {
        return file_exists($fullPath);
    }
}
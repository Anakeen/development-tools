<?php

namespace Dcp\DevTools\Deploy;

use Dcp\DevTools\Webinst\Webinst;

class Deploy
{
    protected $options;
    protected $tmpdir;

    public function __construct(Array $options = []) {
        $missingOptions = [];

        if (!isset($options['url'])) {
            $missingOptions['url'] = 'You must provide an url';
        }
        if (!isset($options['context'])) {
            $missingOptions['context'] = 'You must provide a context';
        }
        if (!isset($options['port'])) {
            $missingOptions['port'] = 'You must provide a port';
        }

        if (0 < count($missingOptions)) {
            throw new Exception(
                sprintf(
                    "Missing options:\n%s",
                    '  - ' . implode("\n  - ", $missingOptions)
                )
            );
        }

        $this->options = $options;
    }

    public function deploy() {

        if (isset($this->options['webinst'])) {
            $webinst = $this->options['webinst'];
        } else {
            $this->tmpdir = tempnam(sys_get_temp_dir(), 'dev');
            if (file_exists($this->tmpdir)) {
                unlink($this->tmpdir);
            }
            if (false === mkdir($this->tmpdir)) {
                throw new \Exception(
                    sprintf(
                        'Could not create tmpdir: %s',
                        $this->tmpdir
                    )
                );
            }
            $webinstBuilder = new Webinst($this->options['sourcePath']);
            if(isset($this->options['auto-release'])) {
                $webinstBuilder->setConfProperty(
                    'release',
                    $webinstBuilder->getConf('release') . strftime(".%Y%m%d.%H%M%S")
                );
            }
            $webinst = $webinstBuilder->makeWebinst($this->tmpdir);
        }

        $request = curl_init();

        try {

            $data = [
                'deployWebinst' => true,
                'webinst' => new \CURLFile($webinst),
                'context' => $this->options['context']
            ];

            foreach (
                $this->options['additional_args'] as $additionalArgKey =>
                $additionalArg
            ) {
                $data["additional_args[$additionalArgKey]"] = $additionalArg;
            }

            if (isset($this->options['action'])) {
                $data['action'] = $this->options['action'];
            }

            curl_setopt_array(
                $request,
                [
                    CURLOPT_URL => $this->options['url'] . '/wiff.php',
                    CURLOPT_PORT => $this->options['port'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_POSTFIELDS => $data
                ]
            );
            //FIXME: handle auth
            $res = curl_exec($request);

            if (isset($this->tmpdir)) {
                $this->recursiveRmDir($this->tmpdir);
            }

            if (false === $res) {
                throw new Exception(
                    sprintf(
                        "An error occured during connection to server: %d (%s)",
                        curl_errno($request),
                        curl_error($request)
                    )
                );
            }

            $httpCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
            if (401 === $httpCode) {
                throw new Exception(
                    "Authentication is required"
                );
            } elseif (403 === $httpCode) {
                throw new Exception(
                    "invalid credentials for %s"
                );
            } elseif (299 < $httpCode) {
                throw new Exception(
                    sprintf(
                        "%s returned an error status code: %d",
                        $httpCode
                    )
                );
            }
        } catch (CurlException $e) {
            curl_close($request);
            throw $e;
        }

        curl_close($request);

        $result = json_decode($res, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(
                sprintf(
                    "An error occured while reading output from server: %d (%s).\nRaw output:\n%s",
                    json_last_error(),
                    json_last_error_msg(),
                    $res
                )
            );
        }

        return $result;
    }

    private function recursiveRmDir($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRmDir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}


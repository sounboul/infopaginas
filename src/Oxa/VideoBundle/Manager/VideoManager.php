<?php

namespace Oxa\VideoBundle\Manager;

use Domain\SiteBundle\Utils\Helpers\SiteHelper;
use Gaufrette\Filesystem;
use Oxa\VideoBundle\Entity\VideoMedia;
use Oxa\VideoBundle\Uploader\VideoLocalFileUploader;
use Oxa\VideoBundle\Uploader\VideoRemoteFileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VideoManager
{
    private static $allowedMimeTypes = array(
        'video/mp4',
    );

    private $filesystem;
    private $videoProjectAPIManager;
    private $videoMediaAPIManager;
    private $videoLocalFileUploader;
    private $videoRemoteFileUploader;

    private $videoMediaManager;

    private $videoEmbedAPIManager;

    public function __construct(
        Filesystem $filesystem,
        VideoMediaManager $videoMediaManager
    ) {
        $this->filesystem = $filesystem;
        $this->videoMediaManager = $videoMediaManager;
    }

    public function listProjects() : array
    {
        return $this->videoProjectAPIManager->list();
    }

    public function showProject(string $hash) : array
    {
        return $this->videoProjectAPIManager->show($hash);
    }

    public function createProject(array $data) : array
    {
        return $this->videoProjectAPIManager->create($data);
    }

    public function updateProject(string $hash, array $data) : array
    {
        return $this->videoProjectAPIManager->update($hash, $data);
    }

    public function removeProject(string $hash) : array
    {
        return $this->videoProjectAPIManager->remove($hash);
    }

    public function copyProject(string $hash) : array
    {
        return $this->videoProjectAPIManager->copy($hash);
    }

    public function listMedia() : array
    {
        return $this->videoMediaAPIManager->list();
    }

    public function showMedia(string $hash) : array
    {
        return $this->videoMediaAPIManager->show($hash);
    }

    public function updateMedia(string $hash, array $data) : array
    {
        return $this->videoMediaAPIManager->update($hash, $data);
    }

    public function removeMedia(string $hash) : array
    {
        return $this->videoMediaAPIManager->remove($hash);
    }

    public function copyMedia(string $hash) : array
    {
        return $this->videoMediaAPIManager->copy($hash);
    }

    public function statsMedia(string $hash) : array
    {
        return $this->videoMediaAPIManager->stats($hash);
    }

    public function uploadLocalFile(UploadedFile $file, array $data = []) : VideoMedia
    {
        $uploadedFileData = $this->uploadLocalFileData($file, $data);

        return $this->videoMediaManager->save($uploadedFileData);
    }

    public function uploadLocalFileData(UploadedFile $file, array $data = []) : array
    {
        // Check if the file's mime type is in the list of allowed mime types.
        if (!in_array($file->getClientMimeType(), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }

        // Generate a unique filename based on the date and add file extension of the uploaded file
        $path = sprintf('%s/%s/', date('Y'), date('m'));
        $filename = sprintf('%s.%s', date('Y'), date('m'), uniqid(), $file->getClientOriginalExtension());

        $adapter = $this->filesystem->getAdapter();
        $adapter->setMetadata($path.$filename, array('contentType' => $file->getClientMimeType()));
        $uploadedSize = $adapter->write($path.$filename, file_get_contents($file->getPathname()));

        if ($uploadedSize) {
            throw new \InvalidArgumentException(sprintf('File '.$filename.' is not uploaded. Please contact administrator'));
        }

        $video = [
            'filename'  => $filename,
            'type'      => $file->getClientMimeType(),
            'filepath'  => $filepath,
        ];

        return $video;
    }

    public function uploadRemoteFile(string $url, array $data = [])
    {
        $headers = SiteHelper::checkUrlExistence($url);

        if ($headers && in_array($headers['content_type'], SiteHelper::$videoContentTypes)) {
            $uploaderRequestData = ['url' => $url];
            $fileUploader = $this->videoRemoteFileUploader->setData(array_merge($uploaderRequestData, $data));

            $videoMediaData = $fileUploader->upload();

            return $this->videoMediaManager->save($videoMediaData);
        }

        return false;
    }

    public function getEmbedCode(string $hash, array $dimensions = [])
    {
        return $this->videoEmbedAPIManager->get($hash, $dimensions);
    }
}

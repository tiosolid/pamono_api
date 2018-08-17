<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;

class ImagesController extends Controller
{
    /**
    * @Get("/images", name="images_get")
    */
    public function getAction(Request $request)
    {
        return $this->json(['msg' => "Only GET and POST methods are supported. You can get authorized using a Bearer Token in the request header"], 200);
    }

    /**
     * @Post("/images", name="images_post")
     */
    public function postAction(Request $request)
    {
        //Validate authentication
        $auth_status = $this->checkAuthorization($request);
        if ($auth_status != null) { return $auth_status; }
        
        //Decode Base64 file
        $file_data = base64_decode($request->getContent());
        if ($file_data === false) {
            return $this->json(['error' => ['status' => 400, 'message' => 'The base64 data is invalid']], 400);
        }
        
        //Save file in a path
        $image_path = $this->getNewImagePath();
        $file_status = file_put_contents($image_path, $file_data);
        if ($file_status === false) {
            return $this->json(['error' => ['status' => 400, 'message' => 'Error while creating the new image file']], 400);
        }
        
        //Validate the format
        $image_data = getimagesize($image_path);
        if ($image_data === false) {
            unlink($image_path);
            return $this->json(['error' => ['status' => 400, 'message' => 'File is not a valid image']], 400);
        }

        if (($image_data[2] != IMG_JPG) || ($image_data[2] != IMG_JPEG)) {
            unlink($image_path);
            return $this->json(['error' => ['status' => 400, 'message' => 'Only JPG / JPEG files are accepted']], 400);
        }

        //Validate the Size (file size and H/W)
        $image_max_width = $this->getParameter('image_max_width');
        $image_max_height = $this->getParameter('image_max_height');
        $image_max_size = $this->getParameter('image_max_size');
        if (($image_data[0] > $image_max_width) || ($image_data[1] > $image_max_height)) {
            unlink($image_path);
            $message = "The image is too large. The maximum supported dimension is $image_max_width x $image_max_height pixels";
            return $this->json(['error' => ['status' => 400, 'message' => $message]], 400);
        }

        if (filesize($image_path) > $image_max_size) {
            unlink($image_path);
            $kb_size = $image_max_size / 1024;
            $message = "The image file is too big. The maximum supported size is {$kb_size}KB";
            return $this->json(['error' => ['status' => 400, 'message' => $message]], 400);
        }

        //Save imagem with final name extension
        $image_extension = ($image_data[2] == IMG_JPG) ? '.jpg' : '.jpeg';
        rename($image_path, $image_path . $image_extension);

        //Return image url
        $upload_folder = $this->getParameter('upload_folder');
        $file_name = basename($image_path);
        $image_url = "{$request->getSchemeAndHttpHost()}/$upload_folder/$file_name$image_extension";

        return $this->json(['image_url' => $image_url], 201);
    }

    /**
    * @Put("/images", name="images_put")
    */
    public function putAction() {
        return $this->json(['msg' => "Method not supported"], 405);
    }

    /**
    * @Delete("/images", name="images_delete")
    */
    public function deleteAction() {
        return $this->json(['msg' => "Method not supported"], 405);
    }

    private function checkAuthorization(Request $request)
    {
        $authorization_header = $request->headers->get('authorization');
        if ($authorization_header == null) {
            return $this->json(['error' => 'unauthorized_client', 'error_description' => 'This API requires authorization via Bearer Token'], 401);
        }

        //Check Authorization Method
        list($auth_method, $auth_token) = explode(' ', $authorization_header);
        if ($auth_method != 'Bearer') {
            return $this->json(['error' => 'unsupported_grant_type', 'error_description' => 'Only "Bearer" authorization method is supported'], 401);
        }

        //Check if token is correct
        $bearer_token = $this->getParameter('bearer_token');
        if ($auth_token != $this->getParameter('bearer_token')) {
            return $this->json(['error' => 'unauthorized_client', 'error_description' => 'Unauthorized'], 401);
        }

        return null;
    }

    private function getNewImagePath()
    {
        $upload_path = $this->getParameter('upload_path');
        if (!file_exists($upload_path)) { mkdir($upload_path, 0777, true); }

        $photo_path = null;
        do {
            $photo_path = $upload_path . '/' . uniqid();
        } while (file_exists($photo_path));
        
        return $photo_path;
    }
}

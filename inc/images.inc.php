<?php
    class ImageHandler
    {
        /**
         * @var $save_dir -> folder where images are saved
         */
        public $save_dir;
        public $max_dims;
        //sets the $save_dir to instantiate
        public function __construct($save_dir, $max_dims = array(350,240))
        {
            $this->save_dir = $save_dir;
            $this->max_dims = $max_dims;
        }

        /**
         * Resizes/resamples an image uploaded via a web form
         * @param array $upload the array contained in $_FILES
         * @return string the path to the resized upload file
         */
        public function processUploadedImage($file, $rename=TRUE)
        {
            //process the image
            //separate the uploaded file array
            list($name, $type, $tmp, $err, $size) = array_values($file);
            //throw exception if there is an error
            if($err != UPLOAD_ERR_OK) {
                throw new Exception('An error occurred with the upload!');
                return;
            }
            //generate a resized image
            $this->doImageResize($tmp);
            //check if directory exists
            $this->checkSaveDir();
            if($rename === TRUE){
                $img_ext = $this->getImageExtension($type);
                $name = $this->renameFile($img_ext);
            }
            //create full path for saving
            $filepath = $this->save_dir.$name;
            //store the absolute path to move the image
            $absolute = $_SERVER['DOCUMENT_ROOT'].$filepath;
            //save the image
            if(!move_uploaded_file($tmp, $absolute)) {
                throw new Exception("Could not save the uploaded file!");
            }
            return $filepath;
        }

        //generate a unique file name
        private function renameFile($ext)
        {
            return time() . '_' . mt_rand(1000,9999) . $ext;
        }

        //determine file type and extension
        private function getImageExtension($type)
        {
            switch ($type) {
                case 'image/gif':
                    return '.gif';
                case 'image/jpeg':
                case 'image/pjpeg':
                    return '.jpg';
                case 'image/png':
                    return '.png';
                default:
                    throw new Exception('File type is not recognized!');
            }
        }

        private function checkSaveDir()
        {
            //determines the path to check
            $path = $_SERVER['DOCUMENT_ROOT'].$this->save_dir;
            //check if dir exists
            if(!is_dir($path)){
                //create
                if(!mkdir($path, 0777, TRUE)){
                    throw new Exception("Can't create the directory!");
                }
            }
        }

        private function getNewDims($img)
        {
            //necessary variables for processing
            list($src_w, $src_h) = getimagesize($img);
            list($max_w, $max_h) = $this->max_dims;
            //check that the image is bigger than the maximum dimensions
            if($src_w > $max_w  || $src_h > $max_h){
                //determine the scale to which the image will be resized
                $s = min($max_w/$src_w,$max_h/$src_h);
            } else {
                //if the image is smaller, keep the dimensions
                $s = 1;
            }
            //get new dimensions
            $new_w = round($src_w * $s);
            $new_h = round($src_h * $s);
            //return new dimensions
            return array($new_w, $new_h, $src_w, $src_h);
        }

        //determine which function to use
        private function getImageFunctions($img)
        {
            $info = getimagesize($img);
            switch($info['mime']){
                case 'image/jpeg':
                case 'image/pjpeg':
                    return array('imagecreatefromjpeg','imagejpeg');
                    break;
                case 'image/gif':
                    return array('imagecreatefromgif','imagegif');
                    break;
                case 'image/png':
                    return array('imagecreatefrompng','imagepng');
                    break;
                default:
                    return FALSE;
                    break;
            }
        }

        private function doImageResize($img)
        {
            //determine the new dimesnsions
            $d = $this->getNewDims($img);
            //determine the functions you need to use
            $funcs = $this->getImageFunctions($img);
            //create the image resources for resampling
            $src_img = $funcs[0]($img);
            $new_img = imagecreatetruecolor($d[0],$d[1]);
            if(imagecopyresampled(
                $new_img, $src_img, 0, 0, 0, 0, $d[0], $d[1], $d[2], $d[3]
                )){
                imagedestroy($src_img);
                if($new_img && $funcs[1]($new_img, $img)){
                    imagedestroy($new_img);
                } else {
                    throw new Exception('Failed to save the new image!');
                }
            } else {
                throw new Exception('Could not resample the image!');
            }
        }

    }
?>
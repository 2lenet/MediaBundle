<?php

namespace Neuros\MediaBundle\Controller;

use Neuros\MediaBundle\Entity\File;
use Neuros\MediaBundle\Entity\Folder;
use Neuros\MediaBundle\Form\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function getFolderAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $folder = null;
        if($id){
            $folder = $em->getRepository('NeurosMediaBundle:Folder')->find($id);
        }

        /*
         * ACA : pour gérer l'affichage de l'arborescance des dossiers entre admin clicom et admin piece
         */
        $type = $this->getRequest()->query->get('type');
        $session  = $this->getRequest()->getSession();
        if($type=='piece'){
            $type = '_pd';
        }elseif($type=='clicom'){
            $type = '_clicom';
        }
        else{
            $type = '_clicom';
            if($session->has('documentation_admin_type')){
                $type = $session->get('documentation_admin_type');
            }
        }
        $session->set('documentation_admin_type',$type);

        if(!$folder){
            $query = $em->createQuery(
                'SELECT f 
                FROM NeurosMediaBundle:Folder f
                LEFT JOIN f.parent p
                WHERE p.id IS NULL
                '
            );
            $folders = $query->getResult();
            $folder = new Folder();
            $folder->setId(0);
            $folder->setName('Racine');
            $folder->setChildren($folders);
            $folder->setFiles(array());
        }

        foreach($folder->getFiles() as $f){
            $f->testFileExist();
        }
        return $this->render('NeurosMediaBundle:Default:index'.$type.'.html.twig',
            array(
                'folder' => $folder
            )
        );
    }

    // edit and create folder
    public function folderAction()
    {
        $request = $this->getRequest();
        $name = $request->request->get('name');
        $name = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $name);
        $type_action = $request->request->get('type');
        $status = 1;
        $msg= '';
        if($name){
            if($type_action == "edit"){
                $em = $this->getDoctrine()->getManager();
                $id =$request->request->get('id');
                if($id){
                    $dossier = $em->getRepository('NeurosMediaBundle:Folder')->find($id);

                    if($dossier){
                        if($request->request->get('status') == 'true'){
                            $dossier->setEnabled(1);
                        }else{
                            $dossier->setEnabled(0);
                        }
                        $dossier->setName($name);
                        $em->persist($dossier);
                        $em->flush();

                    }else{
                        $status = 0;
                        $msg = 'Dossier introuvable';
                    }

                }else{
                    $status = 0;
                    $msg = 'Dossier introuvable';
                }

            }else{
                    $id_parent =$request->request->get('id');
                    $em = $this->getDoctrine()->getManager();
                    $parent = null;
                    if($id_parent){
                        $parent = $em->getRepository('NeurosMediaBundle:Folder')->find($id_parent);
                        if(!$parent){
                            $status = 0;
                            $msg = 'Dossier parent non existant.';
                        }
                    }

                    if($status){
                        $dossier = new Folder();

                        $dossier->setName($name);
                        $dossier->setParent($parent);
                        if($request->request->get('status') == 'true'){
                            $dossier->setEnabled(1);
                        }else{
                            $dossier->setEnabled(0);
                        }
                        $em->persist($dossier);
                        $em->flush();
                    }
            }

        }else{
            $status = 0;
            $msg = 'Le nom du fichier est obligatoire';
        }


        return new Response(json_encode(
            array(
                'status'=>$status,
                'msg'=>$msg
            )
        ));
    }

    public function deleteFolderAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $folder = null;
        if($id){
            $folder = $em->getRepository('NeurosMediaBundle:Folder')->find($id);
        }
        $parent_id = 0;
        if($folder){
            if($folder->getParent()){
                $parent_id= $folder->getParent()->getId();
            }
            $em->remove($folder);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('neuros_media_homepage',array('id'=>$parent_id)));

    }

    public function deleteFileAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $folder = null;
        if($id){
            $file = $em->getRepository('NeurosMediaBundle:File')->find($id);
        }
        $parent_id = 0;
        if($file){
            if($file->getFolder()){
                $parent_id= $file->getFolder()->getId();
            }
            $em->remove($file);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('neuros_media_homepage',array('id'=>$parent_id)));

    }

    public function fileUploadAction($id){
        $err = 'Uploaded';
        $status = 1;
        if (!empty($_FILES)) {
            $em = $this->getDoctrine()->getManager();
            $dossier = $em->getRepository('NeurosMediaBundle:Folder')->find($id);
            if($dossier){
                $tempFile = $_FILES['file']['name'];
                $explode = explode('.',$tempFile);
                $l =count($explode);
                $name = '';
                $file_extension = '';
                if($l>1){
                    $file_extension = $explode[$l-1];
                    for($i=0;$i<($l-1);$i++){

                        $name.= $explode[$i];
                    }
                }

                if($name && $file_extension){
                    $file = new File();
                    $file->setFilename($name);
                    $file->setDescription($name);
                    $file->setMimetype($file_extension);
                    $file->setFolder($dossier);
                    $file->setTempName($_FILES['file']['tmp_name']);
                    try{
                        $em->persist($file);
                        $em->flush();
                    }catch (Exception $e){
                        $status = 0;
                        $err = "Erreur serveeur";
                    }

                }else{
                    $status = 0;
                    $err = "Fichier not supporté.";
                }

            }else{
                $status = 0;
                $err = "Le dossier parent inexistant.";
            }

            $err = $_FILES['file']['name'].' : '.$err;
        }
        //var_dump($_FILES['file']);
        return new Response(json_encode(array('status'=>$status,'msg'=>$err)));
    }

    public function downloadFileAction($id){

        if($id){
            $em = $this->getDoctrine()->getManager();
            $file = $em->getRepository('NeurosMediaBundle:File')->find($id);
            if($file){
                $response = new Response();

                $response->headers->set('Cache-Control', 'private');
                $response->headers->set('Content-type', mime_content_type($file->getPath()));
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->getFilename() . '";');
                $response->headers->set('Content-length', filesize($file->getPath()));

                $response->sendHeaders();

                $response->setContent(readfile($file->getPath()));
            }
        }

        return $this->redirect($this->generateUrl('neuros_media_homepage'));

    }

    /**
     * Displays a form to edit an existing File entity.
     *
     */
    public function editFileAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('NeurosMediaBundle:File')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find File entity.');
        }

        $editForm = $this->createForm(new FileType(), $entity);

        return $this->render('NeurosMediaBundle:Default:editFile.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
     * Edits an existing File entity.
     *
     */
    public function updateFileAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('NeurosMediaBundle:File')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find File entity.');
        }

        $editForm = $this->createForm(new FileType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'flash.update.success');

            return $this->redirect($this->generateUrl('neuros_media_edit_file', array('id' => $id)));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'flash.update.error');
        }

        return $this->render('NeurosMediaBundle:Default:editFile.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }
}

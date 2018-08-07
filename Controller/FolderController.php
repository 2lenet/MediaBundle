<?php

namespace Lle\MediaBundle\Controller;

use Lle\MediaBundle\Entity\File;
use Lle\MediaBundle\Entity\Folder;
use Lle\MediaBundle\Form\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


class FolderController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $root = $em->getRepository('LleMediaBundle:Folder')->findOneBy(['slug'=>'/']);
        if (!$root) {
            $root = new Folder();
            $root->setTitle('Media');
            $em->persist($root);
            $em->flush();
        }

        return $this->render('LleMediaBundle:Folder:index.html.twig',
            array(
                'folder' => $root
            )
        );
    }
    public function folderIndexAction(Folder $folder)
    {
        return $this->render('LleMediaBundle:Folder:index.html.twig',
            array(
                'folder' => $folder
            )
        );
    }

    // edit and create folder
    public function newFolderAction(Request $request, Folder $parent)
    {
        $em = $this->getDoctrine()->getManager();
        $folder = new Folder();
        $folder->setName($request->get('folder_name'));
        $folder->setParent($parent);
        $em->persist($folder);
        $em->flush();
        return $this->redirectToRoute('lle_media_folder', ['id' => $folder->getId()] );
    }

    public function deleteFolderAction(Folder $folder)
    {
        $em = $this->getDoctrine()->getManager();
        $parent_id= $folder->getParent()->getId();
        $em->remove($folder);
        $em->flush();
        return $this->redirect($this->generateUrl('lle_media_folder',array('id'=>$parent_id)));
    }

    public function deleteFileAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $folder = null;
        if($id){
            $file = $em->getRepository('LleMediaBundle:File')->find($id);
        }
        $parent_id = 0;
        if($file){
            if($file->getFolder()){
                $parent_id= $file->getFolder()->getId();
            }
            $em->remove($file);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('lle_media_index',array('id'=>$parent_id)));

    }

    public function fileUploadAction(Request $request, Folder $folder){
        $em = $this->getDoctrine()->getManager();

        $media = $request->files->get('file');
        $file = new File();
        $file->setFolder($folder);
        $file->upload($media);
        $em->persist($file);
        $em->flush();
        return new JsonResponse(array('success' => true));
    }

    public function downloadFileAction($id){

        if($id){
            $em = $this->getDoctrine()->getManager();
            $file = $em->getRepository('LleMediaBundle:File')->find($id);
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

        return $this->redirect($this->generateUrl('lle_media_index'));

    }

    /**
     * Displays a form to edit an existing File entity.
     *
     */
    public function editFileAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LleMediaBundle:File')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find File entity.');
        }

        $editForm = $this->createForm(new FileType(), $entity);

        return $this->render('LleMediaBundle:Default:editFile.html.twig', array(
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

        $entity = $em->getRepository('LleMediaBundle:File')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find File entity.');
        }

        $editForm = $this->createForm(new FileType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'flash.update.success');

            return $this->redirect($this->generateUrl('Lle_media_edit_file', array('id' => $id)));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'flash.update.error');
        }

        return $this->render('LleMediaBundle:Default:editFile.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }
}

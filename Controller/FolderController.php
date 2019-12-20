<?php

namespace Lle\MediaBundle\Controller;

use Lle\MediaBundle\Entity\File;
use Lle\MediaBundle\Entity\Folder;
use Lle\MediaBundle\Form\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\NotNull;

class FolderController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $root = $em->getRepository('LleMediaBundle:Folder')->findOneBy(['parent'=>null]);
        if (!$root) {
            $root = new Folder();
            $root->setName('');
            $root->setPath('');
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
        $folder->updatePath();
        $em->persist($folder);
        $em->flush();
        return $this->redirectToRoute('lle_media_folder', ['id' => $folder->getId()] );
    }

    public function editFolderAction(Request $request, Folder $folder)
    {
        $form = $this->createFormBuilder($folder)
            ->add('name', TextType::class, ['required' => true, 'constraints' => [new NotNull()]])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($folder);
            $this->getDoctrine()->getManager()->flush();
            if ($folder->getParent()) {
                return $this->redirectToRoute('lle_media_folder', ['id' => $folder->getParent()->getId()]);
            }
            return $this->redirectToRoute('lle_media_index');
        }
        return $this->render('LleMediaBundle:Folder:edit.html.twig', ['form' => $form->createView(), 'folder' => $folder]);
    }

    public function deleteFolderAction(Folder $folder)
    {
        $em = $this->getDoctrine()->getManager();
        $parent_id= $folder->getParent()->getId();
        $em->remove($folder);
        $em->flush();
        return $this->redirectToRoute('lle_media_folder', ['id' => $parent_id]);
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
        return $this->redirectToRoute('lle_media_folder', ['id'=>$parent_id]);
    }

    public function fileUploadAction(Request $request, Folder $folder){
        $em = $this->getDoctrine()->getManager();
        $root = $this->getParameter('kernel.root_dir')."/../media/";

        $media = $request->files->get('file');

        $file = new File();
        $file->setFolder($folder);
        $file->upload($media, $root);

        $em->persist($file);
        $em->flush();
        return new JsonResponse(array('success' => true));
    }

    public function downloadFileAction(File $file) {
        $root = $this->getParameter('kernel.root_dir')."/../media/";

        // This should return the file to the browser as response
        $response = new BinaryFileResponse($root.$file->getPath());

        // To generate a file download, you need the mimetype of the file
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        // Set the mimetype with the guesser or manually
        if($mimeTypeGuesser->isSupported()){
            // Guess the mimetype of the file according to the extension of the file
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($root.$file->getPath()));
        } else {
            // Set the mimetype of the file manually, in this case for a text file is text/plain
            $response->headers->set('Content-Type', 'text/plain');
        }

        // Set content disposition inline of the file
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $file->getFilename()
        );

        return $response;
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

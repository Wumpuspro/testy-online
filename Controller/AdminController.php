<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminController
 *
 * @author Npg
 */

namespace tests\testBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use tests\testBundle\Modals\Admin;
use tests\testBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use tests\testBundle\isSession;

class AdminController extends Controller {

    public function adminAction(Request $request) {
// login form - Admin login
        $session = $this->getRequest()->getSession();                  
        $em = $this->getDoctrine()->getManager();                 
        $repositoryA = $em->getRepository('teststestBundle:Admin');     
        if ($request->getMethod() == 'POST') {                          
            $session->clear();                                         
            $username = $request->get('username');                      
            $password = sha1($request->get('password'));                  

            $session = $this->getRequest()->getSession();
            $admin = $repositoryA->findOneBy(array('admin' => $username, 'password' => $password));     
            if ($admin) {
                $aLogin = new Admin();                 
                $aLogin->setUsername($username);        
                $aLogin->setPassword($password);         
                $session->set('aLogin', $aLogin);         
                return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('name' => ucfirst($admin->getName()), 'surname' => ucfirst($admin->getSurname()), 'school' => $admin->getSchool()));         // renderuje profil admina
            } else {        // pokud shoda neexistuje
                return $this->render('teststestBundle:Admin:admin.html.twig', array('error' => 'Uživatelské jméno nebo heslo je nesprávné'));         
            }
        } else {                                         
            $a = new isSession\exist;
            $admin = $a->admin($session, $repositoryA);
            if ($admin) {       // shoda existuje
                return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool()));   
            }
            return $this->render('teststestBundle:Admin:admin.html.twig');        
        }
    }

    public function registerAction(Request $request) {
        // register user
        $session = $this->getRequest()->getSession();
        $em = $this->getDoctrine()->getManager();                
        $repositoryA = $em->getRepository('teststestBundle:Admin');

        $a = new isSession\exist;
        $admin = $a->admin($session, $repositoryA);
        if ($admin) {
            if ($request->getMethod() == 'POST') {          
                $name = trim($request->get('name'));            
                $surname = trim($request->get('surname'));
                $username_reg = trim($request->get('username'));
                $email = trim($request->get('email'));
                $password_reg = $request->get('password');
                $admin_school = trim($request->get('school'));

                $repositoryU = $em->getRepository('teststestBundle:User');                     
                $sameUser = $repositoryU->findOneBy(array('username' => $username_reg));        
                if (strtolower($username_reg) === 'admin' || strtolower($username_reg) === 'administrator') {   
                    return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool(), 'error' => "Uživatelské jméno nemůže obsahovat slova admin nebo administrator."));   // vypise se chybove hlaseni
                } else if ($sameUser == NULL) {                       
                    $user = new User();                       

                    $user->setName($name);                     
                    $user->setSurname($surname);
                    $user->setUsername($username_reg);
                    $user->setEmail($email);
                    $user->setPassword(sha1($password_reg));
                    $user->setSchool($admin_school);

                    $em->persist($user);                            
                    $em->flush();                                      

                    $subject = "Registrační údaje";                               
                    $headers = "FROM: testy@online.cz" . "\r\n";
                    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                    $body = $this->renderView("teststestBundle:Admin:registerEmailLayout.html.twig", array('name' => $name, 'surname' => $surname, 'username' => $username_reg, 'email' => $email, 'password' => $password_reg, 'school' => $admin_school));
                    mail($email, $subject, $body, $headers);

                    return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool(), 'message' => "Uživatel $username_reg byl úspěšně registrován.")); // pokud byl uzivatel registrovan vypise se hlaseni
                } else {        
                    return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool(), 'error' => "Uživatel $username_reg již existuje, zadejte prosím jiné uživatelské jméno."));   // vypise se chybove hlaseni
                }
            } else {        
                if ($admin) {       
                    return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool()));    
                }
            }
        }
        return $this->render('teststestBundle:Default:home.html.twig');        
    }

    public function dataAction() {
     
        $em = $this->getDoctrine()->getManager();                 
        $repositoryA = $em->getRepository('teststestBundle:Admin');
        $repositoryU = $em->getRepository('teststestBundle:User');
        $session = $this->getRequest()->getSession();

        $a = new isSession\exist;
        $admin = $a->admin($session, $repositoryA);
        if ($admin) {
            $selectUser = $repositoryU->findBy(array('school' => $admin->getSchool()));
            return $this->render('teststestBundle:Admin:data.html.twig', array('username' => $admin, 'users' => $selectUser));
        }
        return $this->render('teststestBundle:Default:home.html.twig');
    }

    public function dataUserAction() {
        
        $em = $this->getDoctrine()->getManager();                
        $repositoryA = $em->getRepository('teststestBundle:Admin');
        $repositoryAn = $em->getRepository('teststestBundle:Answer');
        $repositoryQ = $em->getRepository('teststestBundle:Question');
        $repositoryU = $em->getRepository('teststestBundle:User');
        $session = $this->getRequest()->getSession();

        $a = new isSession\exist;
        $admin = $a->admin($session, $repositoryA);
        if ($admin) {
            $id = $_COOKIE['id'];      
            $selectUser = $repositoryU->findBy(array('id' => $id));     
            $selectTest = $em->createQueryBuilder()
                    ->select('t')
                    ->from('teststestBundle:Test', 't')
                    ->where('t.userId = :user_id')
                    ->setParameter('user_id', $id)
                    ->getQuery()
                    ->getArrayResult();
            $selectQuestion = $repositoryQ->findAll();
            $selectAnswer = $repositoryAn->findAll();
            return $this->render('teststestBundle:Admin:dataUser.html.twig', array('username' => $admin, 'user' => $selectUser, 'tests' => $selectTest, 'questions' => $selectQuestion, 'answers' => $selectAnswer));
        }
        return $this->render('teststestBundle:Default:home.html.twig');
    }

    public function deleteUserAction(Request $request) {
       
        $em = $this->getDoctrine()->getManager();             
        $session = $this->getRequest()->getSession();
        $repositoryA = $em->getRepository('teststestBundle:Admin');
        $repositoryU = $em->getRepository('teststestBundle:User');
        $repositoryAn = $em->getRepository('teststestBundle:Answer');
        $repositoryQ = $em->getRepository('teststestBundle:Question');

        $a = new isSession\exist;
        $admin = $a->admin($session, $repositoryA);

        if ($admin) {
            if ($request->getMethod() == 'POST') {
                $id = $request->get("user_id");
                $name = $request->get("user_name");
                $surname = $request->get("user_surname");

                $selectTest = $em->createQueryBuilder()       
                        ->select('t')
                        ->from('teststestBundle:Test', 't')
                        ->where('t.userId = :user_id')
                        ->setParameter('user_id', $id)
                        ->getQuery()
                        ->getArrayResult();
                $removingU = $em->createQuery("DELETE FROM teststestBundle:User u WHERE u.id = " . $id);
                $removingU->execute();      

                if ($selectTest) {      
                    for ($i = 0; $i < count($selectTest); $i++) {     
                        $removingT = $em->createQuery("DELETE FROM teststestBundle:Test t WHERE t.userId = " . $id);
                        $removingT->execute();

                        $selectQid = $em->createQueryBuilder()          
                                ->select('partial q.{id}')
                                ->from('teststestBundle:Question', 'q')
                                ->where('q.testId = :test_id')
                                ->setParameter('test_id', $selectTest[$i]['id'])
                                ->getQuery()
                                ->getArrayResult();

                        for ($j = 0; $j < count($selectQid); $j++) {        
                            $removingA = $em->createQuery("DELETE FROM teststestBundle:Answer a WHERE a.questionId = " . $selectQid[$j]['id']);
                            $removingA->execute();
                        }

                       
                        $removingQ = $em->createQuery("DELETE FROM teststestBundle:Question q WHERE q.testId = " . $selectTest[$i]['id']);
                        $removingQ->execute();
                    }
                }

                return $this->render('teststestBundle:Admin:deleteUser.html.twig', array('name' => $name, 'surname' => $surname, 'username' => $admin));
            } else {
                if ($admin) {
                    
                    $id = $_COOKIE['id'];
                    $selectUser = $repositoryU->findBy(array('id' => $id));
                    $selectTest = $em->createQueryBuilder()
                            ->select('t')
                            ->from('teststestBundle:Test', 't')
                            ->where('t.userId = :user_id')
                            ->setParameter('user_id', $id)
                            ->getQuery()
                            ->getArrayResult();
                    $selectQuestion = $repositoryQ->findAll();
                    $selectAnswer = $repositoryAn->findAll();
                    if (!$selectUser) {
                        return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool()));
                    }
                    return $this->render('teststestBundle:Admin:dataUser.html.twig', array('username' => $admin, 'user' => $selectUser, 'tests' => $selectTest, 'questions' => $selectQuestion, 'answers' => $selectAnswer));
                }
            }
        }
        return $this->render('teststestBundle:Default:home.html.twig');
    }

    public function changePasswordAction(Request $request) {
        // zmena hesla uzivatele - Admin
        $em = $this->getDoctrine()->getManager();                 
        $repositoryA = $em->getRepository('teststestBundle:Admin');
        $session = $this->getRequest()->getSession();

        $a = new isSession\exist;
        $admin = $a->admin($session, $repositoryA);
        if ($admin) {           
            if ($request->getMethod() == 'POST') {
                $user_id = $request->get('user_id');
                $new_password = sha1($request->get('new-psw'));
                $for_email = $request->get('new-psw');
                $selectEmail = $em->createQueryBuilder()        
                        ->select('u')
                        ->from('teststestBundle:User', 'u')
                        ->where('u.id = :id')
                        ->setParameter('id', $user_id)
                        ->getQuery()
                        ->getArrayResult();
                $updatePsw = $em->createQueryBuilder()         
                        ->update('teststestBundle:User', 'u')
                        ->set('u.password', '?1')
                        ->where('u.id = :id')
                        ->setParameter('1', $new_password)
                        ->setParameter('id', $user_id)
                        ->getQuery();
                if ($updatePsw->execute()) {       
                    $subject = "Změna hesla";       
                    $headers = "FROM: testy@online.cz" . "\r\n";
                    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                    $email = $selectEmail[0]['email'];
                    $body = $this->renderView("teststestBundle:Admin:changePswEmailLayout.html.twig", array('new_psw' => $for_email));
                    mail($email, $subject, $body, $headers);
                    return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool(), 'message' => 'Uživatelské heslo bylo úspěšně změněno'));
                } else {
                    return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool(), 'error' => 'Uživatelské heslo nebylo změněno'));
                }
            } else {
                return $this->render('teststestBundle:Admin:adminProfile.html.twig', array('username' => $admin, 'school' => $admin->getSchool()));
            }
        }
        return $this->render('teststestBundle:Default:home.html.twig');
    }

}

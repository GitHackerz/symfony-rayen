<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\QuizParticipants;
use App\Entity\Utilisateur;
use App\Form\QuizParticipantsType;
use App\Repository\QuizParticipantsRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz')]
class QuizParticipantsController extends AbstractController
{
    #[Route('/', name: 'app_quiz_participants_index', methods: ['GET'])]
    public function index(QuizRepository $quizRepository, QuizParticipantsRepository $quizParticipantsRepository): Response
    {
        $passedQuizzes = $quizRepository->findPassedQuiz(1);

        return $this->render('quiz_participants/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'quizParticipants' => $quizParticipantsRepository->findAll(),
            'passedQuizzes' => $passedQuizzes,
        ]);
    }

    #[Route('/new', name: 'app_quiz_participants_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quizParticipant = new QuizParticipants();
        $form = $this->createForm(QuizParticipantsType::class, $quizParticipant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quizParticipant);
            $entityManager->flush();

            return $this->redirectToRoute('app_quiz_participants_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('quiz_participants/new.html.twig', [
            'quiz_participant' => $quizParticipant,
            'form' => $form,

        ]);
    }

    #[Route('/{id}/passer-quiz', name: 'app_quizparticipants_passerquiz', methods: ['GET', 'POST'])]
    public function passerQuiz(Request $request, EntityManagerInterface $entityManager, Quiz $quiz): Response
    {
        $quizParticipant = new QuizParticipants();
        $quizParticipant->setQuiz($quiz);
        $participant = $entityManager->getRepository(Utilisateur::class)->find(1);
        $quizParticipant->setParticipant($participant);
        $quizParticipant->setScore(0);
        $questions = $quiz->getQuestions();

        foreach ($questions as $question) {
            $choices = explode(" ", $question->getChoix());

            $question->setChoix1(explode('1)', $choices[0])[1]);
            $question->setChoix2(explode('2)', $choices[1])[1]);
            $question->setChoix3(explode('3)', $choices[2])[1]);
        }

        $form = $this->createForm(QuizParticipantsType::class, null, [
            'questions' => $quiz->getQuestions()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($questions as $question) {
                $reponse = $form->get('question_' . $question->getId())->getData();
                if ($reponse == $question->getReponseCorrecte()) {
                    $quizParticipant->setScore($quizParticipant->getScore() + $question->getScore());
                }
            }
            $entityManager->persist($quizParticipant);
            $entityManager->flush();
            return $this->redirectToRoute('app_quiz_participants_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('quiz_participants/passer-quiz.html.twig', [
            'quiz_participant' => $quizParticipant,
            'form' => $form,
            'questions' => $quiz->getQuestions(),
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_participants_show', methods: ['GET'])]
    public function show(QuizParticipants $quizParticipant): Response
    {
        return $this->render('quiz_participants/show.html.twig', [
            'quiz_participant' => $quizParticipant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_participants_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QuizParticipants $quizParticipant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuizParticipantsType::class, $quizParticipant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_quiz_participants_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('quiz_participants/edit.html.twig', [
            'quiz_participant' => $quizParticipant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_participants_delete', methods: ['POST'])]
    public function delete(Request $request, QuizParticipants $quizParticipant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quizParticipant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quizParticipant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_quiz_participants_index', [], Response::HTTP_SEE_OTHER);
    }
}
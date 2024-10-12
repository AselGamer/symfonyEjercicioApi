<?php

namespace App\Controller;

use App\Entity\Asignatura;
use App\Entity\Clase;
use App\Repository\AsignaturaRepository;
use App\Repository\ClaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class WsController extends AbstractController
{
	#[Route('/ws', name: 'app_ws')]
	public function index(): JsonResponse
	{
		return new JsonResponse(['msg' => 'Hola mundo']);
	}

	#[Route('/ws/get_cursos', name: 'app_ws_cursos', methods: ['GET', 'POST'])]
	public function getCursos(ClaseRepository $claseRepository): JsonResponse
	{
		return $this->convertToJson($claseRepository->findAll());
	}

	#[Route('/ws/add_curso', name: 'app_ws_add_curso', methods: ['POST'])]
	public function addCursos(EntityManagerInterface $entityManager, Request $request): JsonResponse
	{
		$data = $request->toArray();

		$clase = new Clase();

		$clase->setNombre($data['nombre']);

		$entityManager->persist($clase);

		$entityManager->flush();

		return $this->convertToJson($clase);
	}

	#[Route('/ws/delete_curso/{id}', name: 'app_ws_delete_curso', methods: ['DELETE'])]
	public function deleteCursos(EntityManagerInterface $entityManager, ClaseRepository $claseRepository, int $id): JsonResponse
	{
		$clase = $claseRepository->find($id);

		$entityManager->remove($clase);

		$entityManager->flush();

		return $this->convertToJson($clase);
	}

	#[Route('/ws/update_curso/{id}', name: 'app_ws_update_curso', methods: ['PUT'])]
	public function putCursos(EntityManagerInterface $entityManager, ClaseRepository $claseRepository, Request $request, int $id): JsonResponse
	{
		$data = $request->toArray();

		$clase = $claseRepository->find($id);

		$clase->setNombre($data['nombre']);

		$entityManager->persist($clase);

		$entityManager->flush();

		return $this->convertToJson($clase);
	}

	#[Route('/ws/get_asignaturas/{id}', name: 'app_ws_asignaturas', methods: ['GET', 'POST'])]
	public function getAsignaturas(ClaseRepository $claseRepository, AsignaturaRepository $asignaturaRepository, $id): JsonResponse
	{
		$clase = $claseRepository->findOneBy(['id' => $id]);
		return $this->convertToJson($asignaturaRepository->findBy(['id_clase' => $clase]));
	}

	#[Route('/ws/add_asignatura', name: 'app_ws_add_asignatura', methods: ['POST'])]
	public function addAsignatura(
		EntityManagerInterface $entityManager,
		ClaseRepository $claseRepository,
		Request $request
	): JsonResponse {
		$data = $request->toArray();

		$asignatura = new Asignatura();

		$asignatura->setIdClase($claseRepository->find($data['id_curso']));

		$asignatura->setNombre($data['nombre']);

		$asignatura->setDuracion($data['duracion']);

		$entityManager->persist($asignatura);

		$entityManager->flush();

		return $this->convertToJson($asignatura);
	}

	#[Route('/ws/delete_asignatura/{id}', name: 'app_ws_delete_asignatura', methods: ['DELETE'])]
	public function deleteAsignatura(EntityManagerInterface $entityManager, AsignaturaRepository $asignaturaRepository, int $id): JsonResponse
	{
		$asignatura = $asignaturaRepository->find($id);

		$entityManager->remove($asignatura);

		$entityManager->flush();

		return $this->convertToJson($asignatura);
	}

	#[Route('/ws/update_asignatura/{id}', name: 'app_ws_update_asignatura', methods: ['PUT'])]
	public function updateAsignatura(
		EntityManagerInterface $entityManager,
		AsignaturaRepository $asignaturaRepository,
		ClaseRepository $claseRepository,
		Request $request,
		int $id
	): JsonResponse {
		$data = $request->toArray();

		$asignatura = $asignaturaRepository->find($id);

		$asignatura->setIdClase($claseRepository->find($data['id_curso']));

		$asignatura->setNombre($data['nombre']);

		$asignatura->setDuracion($data['duracion']);

		$entityManager->persist($asignatura);

		$entityManager->flush();

		return $this->convertToJson($asignatura);
	}

	private function convertToJson($data)
	{
		$encoders = [new XmlEncoder(), new JsonEncoder()];
		$normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];

		$serializer = new Serializer($normalizers, $encoders);

		// Normalizamos los datos
		$normalized = $serializer->normalize($data, null, [
			DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
			ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
				return $object->getId(); // Para evitar problemas de referencia circular
			}
		]);

		// Convertimos los datos normalizados a JSON
		$jsonContent = $serializer->serialize($normalized, 'json');

		// Retornamos la respuesta en formato JSON
		return JsonResponse::fromJsonString($jsonContent, 200);
	}
}

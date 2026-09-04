<?php

namespace Plugin\Newsletter\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Newsletter\Entity\Subscriber;
use Plugin\Newsletter\Repository\SubscriberRepository;

/**
 * Public signup processor (step 25): re-submitting an already-subscribed
 * email is a success (returns the existing row) rather than a 500 from a
 * unique constraint violation - a visitor re-submitting the form shouldn't
 * see an error for something that isn't one from their point of view.
 *
 * @implements ProcessorInterface<Subscriber, Subscriber>
 */
class SubscriberSignupProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SubscriberRepository $subscriberRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Subscriber
    {
        \assert($data instanceof Subscriber);

        $existing = $this->subscriberRepository->findOneBy(['email' => $data->getEmail()]);
        if (null !== $existing) {
            return $existing;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}

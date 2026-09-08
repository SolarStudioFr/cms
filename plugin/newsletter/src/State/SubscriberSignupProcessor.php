<?php

namespace Plugin\Newsletter\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\PluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Newsletter\Entity\Subscriber;
use Plugin\Newsletter\Repository\SubscriberRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        private readonly PluginRegistry $pluginRegistry,
    ) {
    }

    /**
     * @throws NotFoundHttpException once the plugin is disabled - same 404
     *                                a disabled plugin's other public routes
     *                                already produce (see the Portfolio/News
     *                                providers), so the endpoint behaves as
     *                                if it simply didn't exist.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Subscriber
    {
        \assert($data instanceof Subscriber);

        if (!$this->pluginRegistry->isEnabled('newsletter')) {
            throw new NotFoundHttpException();
        }

        $existing = $this->subscriberRepository->findOneBy(['email' => $data->getEmail()]);
        if (null !== $existing) {
            return $existing;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}

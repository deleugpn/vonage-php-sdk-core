<?php

declare(strict_types=1);

namespace Vonage\Subaccount;

use Vonage\Entity\Hydrator\HydratorInterface;
use Vonage\Subaccount\SubaccountObjects\Account;

class Hydrator implements HydratorInterface
{
    public function hydrate(array $data)
    {
        $subaccount = new Account();
        return $this->hydrateObject($data, $subaccount);
    }

    public function hydrateObject(array $data, $object)
    {
        $object->fromArray($data);
        return $object;
    }
}

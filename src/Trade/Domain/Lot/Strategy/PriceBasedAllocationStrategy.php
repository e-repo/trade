<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Strategy;

use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Entity\Lot;

final class PriceBasedAllocationStrategy implements BidAllocationStrategyInterface
{
    public function allocate(
        Lot $lot,
        BidCollection $existingBids,
        Bid $newBid
    ): AllocationResult {
        $remaining = $newBid->getRequestedVolume();
        $freeVolume = $lot->getFreeVolume();

        $allocatedFromFree = min($remaining, $freeVolume);
        $remaining -= $allocatedFromFree;

        $modifiedBids = new BidCollection();

        if ($remaining > 0) {
            $worseBids = $existingBids->getWorseThan($newBid->getPricePerTon());

            foreach ($worseBids as $bid) {
                if ($remaining <= 0) {
                    break;
                }

                $displacedVolume = min($bid->getAllocatedVolume(), $remaining);
                $bid->displace($displacedVolume);
                $remaining -= $displacedVolume;

                $modifiedBids->add($bid);
            }
        }

        $allocatedVolume = $newBid->getRequestedVolume() - $remaining;

        if ($allocatedVolume > 0) {
            $newBid->allocate($allocatedVolume);
        } else {
            $newBid->reject('Insufficient free volume and no worse bids to displace');
        }

        $totalReserved = $existingBids->getTotalAllocatedVolume() + $newBid->getAllocatedVolume();

        return new AllocationResult(
            modifiedBids: $modifiedBids,
            newReservedVolume: $totalReserved,
        );
    }
}

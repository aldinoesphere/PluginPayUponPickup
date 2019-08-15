<?php

namespace SkrillDevelopment\Services;

use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;

interface BasketServiceContract
{
    /**
     * Gathers address data (billing/invoice and shipping) and returns them as an array.
     *
     * @return Address[]
     */
    public function getCustomerAddressData(): array;

    /**
     * Returns true if the billing address is B2B.
     */
    public function isBasketB2B(): bool;

    /**
     * Fetches current basket and returns it.
     *
     * @return Basket
     */
    public function getBasket(): Basket;

    /**
     * Returns the country code of the billing address as isoCode2.
     *
     * @return string
     */
    public function getBillingCountryCode(): string;

    /**
     * Returns true if the shipping and billing address are equal.
     */
    public function shippingMatchesBillingAddress(): bool;
}

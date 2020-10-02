<?php
namespace Germania\OrderDispatcher;

interface ValidatorInterface
{


    /**
     * Perform user data validation and returns cleaned user data,
     * Other array values passed will be preserved and merged back into the result.
     *
     * If validation fails, a `ItemInvalidUserDataException` must be thrown.
     *
     * @param  array  $input
     * @return array
     *
     * @throws Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException
     */
    public function validate( array $input ): array;
}

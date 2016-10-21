<?php

namespace Enjoin\Exceptions;

class Error
{

    /**
     * @param string $message
     * @throws BootstrapException
     */
    public static function dropBootstrapException($message)
    {
        throw new BootstrapException($message);
    }

    /**
     * @param string $message
     * @throws RecordException
     */
    public static function dropRecordException($message)
    {
        throw new RecordException($message);
    }

    /**
     * @param string $message
     * @throws ValidationException
     */
    public static function dropValidationException($message)
    {
        throw new ValidationException($message);
    }

    /**
     * @param string $message
     * @throws BuilderException
     */
    public static function dropBuilderException($message)
    {
        throw new BuilderException($message);
    }

    /**
     * @param string $message
     * @throws ModelException
     */
    public static function dropModelException($message)
    {
        throw new ModelException($message);
    }

    /**
     * @param string $message
     * @throws CacheException
     */
    public static function dropCacheException($message)
    {
        throw new CacheException($message);
    }

}

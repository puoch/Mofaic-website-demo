<?php

namespace Config;

use App\Libraries\AuditLog;
use App\Libraries\ContentOptions;
use App\Libraries\ContentPolicy;
use App\Libraries\ContentService;
use App\Libraries\Markdown;
use App\Libraries\MediaService;
use App\Libraries\MissionDirectory;
use App\Libraries\DepartmentAccess;
use App\Libraries\DiasporaAccess;
use App\Libraries\FormGuard;
use App\Libraries\MailQueue;
use App\Libraries\Newsletter;
use App\Libraries\StaffDirectory;
use App\Libraries\SubmissionService;
use CodeIgniter\Config\BaseService;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */

    public static function auditLog(bool $getShared = true): AuditLog
    {
        if ($getShared) {
            return static::getSharedInstance('auditLog');
        }

        $request = static::request();

        return new AuditLog(Database::connect(), $request instanceof IncomingRequest ? $request : null);
    }

    public static function departmentAccess(bool $getShared = true): DepartmentAccess
    {
        if ($getShared) {
            return static::getSharedInstance('departmentAccess');
        }

        return new DepartmentAccess(Database::connect());
    }

    public static function contentPolicy(bool $getShared = true): ContentPolicy
    {
        if ($getShared) {
            return static::getSharedInstance('contentPolicy');
        }

        return new ContentPolicy(config(ContentTypes::class), static::departmentAccess());
    }

    public static function content(bool $getShared = true): ContentService
    {
        if ($getShared) {
            return static::getSharedInstance('content');
        }

        return new ContentService(Database::connect(), config(ContentTypes::class), static::auditLog(), config(App::class)->defaultLocale);
    }

    public static function contentOptions(bool $getShared = true): ContentOptions
    {
        if ($getShared) {
            return static::getSharedInstance('contentOptions');
        }

        return new ContentOptions(Database::connect(), config(ContentTypes::class), static::contentPolicy());
    }

    public static function media(bool $getShared = true): MediaService
    {
        if ($getShared) {
            return static::getSharedInstance('media');
        }

        return new MediaService(Database::connect(), config(Media::class), static::auditLog());
    }

    public static function missionDirectory(bool $getShared = true): MissionDirectory
    {
        if ($getShared) {
            return static::getSharedInstance('missionDirectory');
        }

        return new MissionDirectory(Database::connect());
    }

    public static function markdown(bool $getShared = true): Markdown
    {
        if ($getShared) {
            return static::getSharedInstance('markdown');
        }

        return new Markdown();
    }

    public static function formGuard(bool $getShared = true): FormGuard
    {
        if ($getShared) {
            return static::getSharedInstance('formGuard');
        }

        return new FormGuard(config(Submissions::class));
    }

    public static function mailQueue(bool $getShared = true): MailQueue
    {
        if ($getShared) {
            return static::getSharedInstance('mailQueue');
        }

        return new MailQueue(Database::connect());
    }

    public static function submissions(bool $getShared = true): SubmissionService
    {
        if ($getShared) {
            return static::getSharedInstance('submissions');
        }

        return new SubmissionService(Database::connect(), config(Submissions::class), static::auditLog(), static::mailQueue());
    }

    public static function newsletter(bool $getShared = true): Newsletter
    {
        if ($getShared) {
            return static::getSharedInstance('newsletter');
        }

        return new Newsletter(Database::connect(), static::mailQueue(), static::auditLog());
    }

    public static function staffDirectory(bool $getShared = true): StaffDirectory
    {
        if ($getShared) {
            return static::getSharedInstance('staffDirectory');
        }

        return new StaffDirectory();
    }

    public static function diasporaAccess(bool $getShared = true): DiasporaAccess
    {
        if ($getShared) {
            return static::getSharedInstance('diasporaAccess');
        }

        return new DiasporaAccess(Database::connect(), static::departmentAccess(), static::auditLog());
    }
}

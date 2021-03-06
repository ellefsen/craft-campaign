<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;

/**
 * PendingContactRecord
 *
 * @property int         $id                         ID
 * @property string      $pid                        Pending ID
 * @property string      $email                      Email
 * @property int         $mailingListId              Mailing List ID
 * @property string      $source                     Source
 * @property mixed       $fieldData                  Field data
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class PendingContactRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

     /**
     * @inheritdoc
     *
     * @return string the table name
     */
    public static function tableName(): string
    {
        return '{{%campaign_pendingcontacts}}';
    }
}

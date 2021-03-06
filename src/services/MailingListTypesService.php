<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\events\MailingListTypeEvent;
use putyourlightson\campaign\jobs\ResaveElementsJob;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\models\MailingListTypeSiteModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Component;
use putyourlightson\campaign\records\MailingListTypeSiteRecord;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

/**
 * MailingListTypesService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property MailingListTypeModel[] $allMailingListTypes
 */
class MailingListTypesService extends Component
{
    /**
     * @event MailingListTypeEvent
     */
    const EVENT_BEFORE_SAVE_MAILINGLIST_TYPE = 'beforeSaveMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    const EVENT_AFTER_SAVE_MAILINGLIST_TYPE = 'afterSaveMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    const EVENT_BEFORE_DELETE_MAILINGLIST_TYPE = 'beforeDeleteMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    const EVENT_AFTER_DELETE_MAILINGLIST_TYPE = 'afterDeleteMailingListType';

    // Public Methods
    // =========================================================================

    /**
     * Returns all mailing list types
     *
     * @return MailingListTypeModel[]
     */
    public function getAllMailingListTypes(): array
    {
        $mailingListTypeRecords = MailingListTypeRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return MailingListTypeModel::populateModels($mailingListTypeRecords, false);
    }

    /**
     * Returns mailing list type by ID
     *
     * @param int $mailingListTypeId
     *
     * @return MailingListTypeModel|null
     */
    public function getMailingListTypeById(int $mailingListTypeId)
    {
        $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListTypeId);

        if ($mailingListTypeRecord === null) {
            return null;
        }

        /** @var MailingListTypeModel $mailingListType */
        $mailingListType = MailingListTypeModel::populateModel($mailingListTypeRecord, false);

        return $mailingListType;
    }

    /**
     * Returns mailing list type by handle
     *
     * @param string $mailingListTypeHandle
     *
     * @return MailingListTypeModel|null
     */
    public function getMailingListTypeByHandle(string $mailingListTypeHandle)
    {
        $mailingListTypeRecord = MailingListTypeRecord::findOne(['handle' => $mailingListTypeHandle]);

        if ($mailingListTypeRecord === null) {
            return null;
        }

        /** @var MailingListTypeModel $mailingListType */
        $mailingListType = MailingListTypeModel::populateModel($mailingListTypeRecord, false);

        return $mailingListType;
    }

    /**
     * Returns a mailing list type’s site-specific settings.
     *
     * @param int $mailingListTypeId
     *
     * @return MailingListTypeSiteModel[]
     */
    public function getCampaignTypeSites(int $mailingListTypeId): array
    {
        $mailingListTypeSiteRecords = MailingListTypeSiteRecord::find()
            ->where(['mailingListTypeId' => $mailingListTypeId])
            ->all();

        /** @var MailingListTypeSiteModel[] $mailingListTypeSiteModels */
        $mailingListTypeSiteModels = MailingListTypeSiteModel::populateModels($mailingListTypeSiteRecords);

        return $mailingListTypeSiteModels;
    }

    /**
     * Saves a mailing list type.
     *
     * @param MailingListTypeModel $mailingListType  The mailing list type to be saved
     * @param bool|null $runValidation Whether the mailing list type should be validated
     *
     * @return bool Whether the mailing list type was saved successfully
     * @throws \Throwable if reasons
     */
    public function saveMailingListType(MailingListTypeModel $mailingListType, bool $runValidation = null): bool
    {
        $runValidation = $runValidation ?? true;

        $isNew = $mailingListType->id === null;

        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        if ($runValidation AND !$mailingListType->validate()) {
            Craft::info('Mailing list type not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($mailingListType->id) {
            $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListType->id);

            if ($mailingListTypeRecord === null) {
                throw new NotFoundHttpException("No mailing list type exists with the ID '{$mailingListType->id}'");
            }
        }
        else {
            $mailingListTypeRecord = new MailingListTypeRecord();
        }

        // Save old site ID for resaving elements later
        $oldSiteId = $mailingListTypeRecord->siteId;

        $mailingListTypeRecord->setAttributes($mailingListType->getAttributes(), false);

        // Unset ID if null to avoid making postgres mad
        if ($mailingListTypeRecord->id === null) {
            unset($mailingListTypeRecord->id);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the field layout
            $fieldLayout = $mailingListType->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);

            $mailingListType->fieldLayoutId = $fieldLayout->id;
            $mailingListTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Save the mailing list type
            if (!$mailingListTypeRecord->save(false)) {
                throw new Exception('Couldn’t save mailing list type record.');
            }

            // Now that we have an mailing list type ID, save it on the model
            if (!$mailingListType->id) {
                $mailingListType->id = $mailingListTypeRecord->id;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire a 'afterSaveMailingListType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        if (!$isNew) {
            // Re-save the mailing lists in this mailing list type
            Craft::$app->getQueue()->push(new ResaveElementsJob([
                'description' => Craft::t('app', 'Resaving {type} mailing lists ({site})', [
                    'type' => $mailingListType->name,
                    'site' => $mailingListType->getSite()->name,
                ]),
                'elementType' => MailingListElement::class,
                'criteria' => [
                    'siteId' => $oldSiteId,
                    'mailingListTypeId' => $mailingListType->id,
                    'status' => null,
                ],
                'siteId' => $mailingListType->siteId,
            ]));
        }

        return true;
    }

    /**
     * Deletes a mailing list type by its ID
     *
     * @param int $mailingListTypeId
     *
     * @return bool Whether the mailing list type was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteMailingListTypeById(int $mailingListTypeId): bool
    {
        $mailingListType = $this->getMailingListTypeById($mailingListTypeId);

        if ($mailingListType === null) {
            return false;
        }

        return $this->deleteMailingListType($mailingListType);
    }

    /**
     * Deletes a mailing list type
     *
     * @param MailingListTypeModel $mailingListType
     *
     * @return bool Whether the mailing list type was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteMailingListType(MailingListTypeModel $mailingListType): bool
    {
        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            if ($mailingListType->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($mailingListType->fieldLayoutId);
            }

            // Delete the mailing lists
            $mailingLists = MailingListElement::findAll(['mailingListTypeId' => $mailingListType->id]);

            $elements = Craft::$app->getElements();

            foreach ($mailingLists as $mailingList) {
                $elements->deleteElement($mailingList);
            }

            // Delete the mailing list type
            $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListType->id);

            if ($mailingListTypeRecord !== null) {
                $mailingListTypeRecord->delete();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
        }

        return true;
    }
}
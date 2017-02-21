<?php

namespace Drupal\sape\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SapeForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'sape_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Form constructor.
        $form = parent::buildForm($form, $form_state);
        // Default settings.
        $config = $this->config('sape.settings');
        // Page title field.
        $form['USER_ID'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('_SAPE_USER:'),
            '#default_value' => $config->get('sape.USER_ID'),
            '#description' => $this->t('Это ваш уникальный идентификатор (хеш).<br/>Можете найти его на сайте <a href="http://sape.ru/" target="_blank">sape.ru</a> кликнув по кнопке "<strong>добавить площадку</strong>".<br/>Будет похож на что-то вроде <strong>d12d0d074c7ba7f6f78d60e2bb560e3f</strong>.'),
        );
        // Source text field.
        $form['links'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Простые ссылки:'),
            '#default_value' => $config->get('sape.links'),
            '#description' => $this->t('Текстовые и блочные ссылки.'),
        );
        $form['context'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Контекстные ссылки:'),
            '#default_value' => $config->get('sape.context'),
            '#description' => $this->t('Ссылки внутри записей.'),
        );
        $form['articles'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Размещение статей:'),
            '#default_value' => $config->get('sape.articles'),
            '#description' => $this->t('Текстовые и блочные ссылки.'),
        );
        $form['tizers'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Размещение тизеров:'),
            '#default_value' => $config->get('sape.tizers'),
            '#description' => $this->t('Тизерные блоки.'),
        );
        $form['tizers_image'] = array(
            '#type' => 'select',
            '#options' => $this->_getTizersOptions(),
            '#title' => $this->t('Файл изображения тизеров:'),
            '#default_value' => $config->get('sape.tizers_image'),
            '#description' => $this->t('Имя файла, показывающего картинки тизеров.'),
        );
        $form['rtb'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Размещение RTB блоков:'),
            '#default_value' => $config->get('sape.rtb'),
            '#description' => $this->t('RTB блоки.'),
        );
        $form['debug'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Режим отладки:'),
            '#default_value' => $config->get('sape.debug'),
            '#description' => $this->t('Текстовые и блочные ссылки.'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('sape.settings');
        $config->set('sape.USER_ID', $form_state->getValue('USER_ID'));
        $config->set('sape.links', $form_state->getValue('links'));
        $config->set('sape.context', $form_state->getValue('context'));
        $config->set('sape.articles', $form_state->getValue('articles'));
        $config->set('sape.tizers', $form_state->getValue('tizers'));
        $config->set('sape.tizers_image', $form_state->getValue('tizers_image'));
        $config->set('sape.rtb', $form_state->getValue('rtb'));
        $config->set('sape.debug', $form_state->getValue('debug'));

        $config->save();


        if($form_state->getValue('USER_ID'))
        {


            file_put_contents(sprintf('%s/%s.php', DRUPAL_ROOT, $form_state->getValue('USER_ID')), sprintf(
                '<?php define(\'_SAPE_USER\', \'%s\');require_once(\'%s/modules/sape/src/vendor/sape/sape.php\');$sape = new SAPE_articles();echo $sape->process_request();',
                $form_state->getValue('USER_ID'),
                DRUPAL_ROOT
            ));

            if($form_state->getValue('tizers')){
                $file = $form_state->getValue('tizers_image');
                $file = $this->_getTizersOptions()[$file];

                file_put_contents(sprintf('%s/%s', DRUPAL_ROOT, $file), sprintf(
                    '<?php define(\'_SAPE_USER\', \'%s\');require_once(\'%s/modules/sape/src/vendor/sape/sape.php\');$sape = new SAPE_client(array(\'charset\' => \'UTF-8\'));$sape->show_image();',
                    $form_state->getValue('USER_ID'),
                    DRUPAL_ROOT
                ));
            }

        }
        return parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'sape.settings',
        ];
    }

    protected function _getTizersOptions()
    {
        return array('img.php', 'image.php', 'photo.php', 'wp-img.php', 'wp-image.php', 'wp-photo.php');
    }

}
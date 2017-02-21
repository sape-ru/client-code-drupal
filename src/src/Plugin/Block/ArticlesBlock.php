<?php

namespace Drupal\sape\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sape\vendor\sape;
/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "articles_block",
 *   admin_label = @Translation("Sape Articles"),
 * )
 */
class ArticlesBlock extends BlockBase implements BlockPluginInterface {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $default_config = \Drupal::config('sape.settings');
        $config = $this->getConfiguration();
        if($default_config->get('sape.USER_ID') && ($default_config->get('sape.articles'))){

            if(!defined('_SAPE_USER')) {
                define('_SAPE_USER', $default_config->get('sape.USER_ID'));
            }

            $sape = \Drupal\sape\vendor\sape\SAPE_articles::getInstance( array(
                    'charset'                 => 'UTF-8',
                    'multi_site'              => true,
                    'show_counter_separately' => true,
                    'force_show_code' => $default_config->get('sape.debug')
                )
            );

            $data = $sape->return_announcements( $config['count']);

            if($data){
                return array(
                    '#type' => 'HtmlTag',
                    '#tag'=>'div',
                    '#markup' => 'data',
                    '#post_render'=>array(function () use($data){
                        return $data;
                    }),
                    '#cache' => array('max-age'=>0)
                );

            }



        }

        return array(
            '#markup' => $config['content'],
            '#cache' => array('max-age'=>0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return array();

        /**
         * Вот тут - у нас большая проблема, если включать - падает плагин
         */
        $default_config = \Drupal::config('sape.settings');


        return array(
            'count' => $default_config->get('articles.count'),
            'context' => $default_config->get('articles.count')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);

        $config = $this->getConfiguration();

        $form['count'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Количество анонсов'),
            '#description' => $this->t('Укажите необходимое количество выводимых анонсов'),
            '#default_value' => isset($config['count']) ? $config['count'] : '',
        );

        $form['content'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Альтернативный текст'),
            '#description' => $this->t('Укажите альтернативный текст, выводимый, если нет анонсов статей'),
            '#default_value' => isset($config['content']) ? $config['content'] : '',
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->configuration['count'] = $form_state->getValue('count');
        $this->configuration['content'] = $form_state->getValue('content');

        /*
         * $this->setConfigurationValue('loremipsum_block_settings', $form_state->getValue('loremipsum_block_settings'));
         *
         * */
    }
}
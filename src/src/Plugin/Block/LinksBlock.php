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
 *   id = "links_block",
 *   admin_label = @Translation("Sape Links"),
 * )
 */
class LinksBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {

        $default_config = \Drupal::config('sape.settings');
        $config = $this->getConfiguration();
        if($default_config->get('sape.USER_ID') && ($default_config->get('sape.links'))){

            if(!defined('_SAPE_USER')) {
                define('_SAPE_USER', $default_config->get('sape.USER_ID'));
            }

            $sape = \Drupal\sape\vendor\sape\SAPE_client::getInstance( array(
                    'charset'                 => 'UTF-8',
                    'multi_site'              => true,
                    'show_counter_separately' => true,
                    'force_show_code' => $default_config->get('sape.debug')
                )
            );

            $data = $sape->return_links( $config['count'], array(
                'as_block' => $config['block'],
                'block_orientation' => $config['orientation']
            ));

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
    public function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);

        $config = $this->getConfiguration();

        $form['count'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Количество ссылок'),
            '#description' => $this->t('Укажите необходимое количество выводимых ссылок'),
            '#default_value' => isset($config['count']) ? $config['count'] : '',
        );

        $form['block'] = array(
            '#type' => 'select',
            '#title' => $this->t('Формат'),
            '#options' => array(0=>'Текст',1=>'Блок'),
            '#description' => $this->t('Укажите необходимое количество выводимых ссылок'),
            '#default_value' => isset($config['block']) ? $config['block'] : 0,
        );

        $form['orientation'] = array(
            '#type' => 'select',
            '#options' => array(0=>'Вертикально',1=>'Горизонтально'),
            '#title' => $this->t('Ориентация блока'),
            '#description' => $this->t('Укажите необходимое количество выводимых ссылок'),
            '#default_value' => isset($config['orientation']) ? $config['orientation'] : 0,
        );

        $form['content'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Альтернативный текст'),
            '#description' => $this->t('Укажите альтернативный текст, выводимый, если нет ссылок'),
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
        $this->configuration['block'] = $form_state->getValue('block');
        $this->configuration['orientation'] = $form_state->getValue('orientation');
    }
}
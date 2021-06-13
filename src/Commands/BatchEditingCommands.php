<?php

namespace Drupal\batch_editing\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Cache\Cache;
use Drush\Commands\DrushCommands;
use Exception;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class BatchEditingCommands extends DrushCommands {
  /**
   * 4つの編集ルールに関して一括処理を行ないます
   * @command batch_editing
   */
  public function batch_editing() {
    // コンテンツ編集ルール1
    // ・node.type = article, pageに対し実行
    // ・フィールドbodyに対し文字列置換を行なう
    // ・delicious->yummy, https://www.drupal.org->https://WWW.DRUPAL.ORG
    try {
      // データベースに接続
      $connection = \Drupal::service("database");
      // テーブルnodeを読みに行き、nidとtypeを取得
      $result = $connection->query("SELECT nid, type FROM node")->fetchAll();
      foreach($result as $record) {
        if ($record->type != "recipe") {
          $this->replacement("node__body", $record->nid, "body_value", "delicious", "yummy");
          $this->replacement("node__body", $record->nid, "body_value", "https://www.drupal.org", "https://WWW.DRUPAL.ORG");
        }
      }
    } catch (Exception $error) {
      echo($error->getMessage());
    } finally {
      echo("編集ルール1おわり" . PHP_EOL);
    }

    // コンテンツ編集ルール2
    // ・すべてのページに対し実行
    // ・フィールドtitleに対し文字列置換を行なうnode_field_data
    // ・Umami->this site
    $result = \Drupal::database()->query("SELECT entity_id FROM node_field_data")->fetchAll();
    foreach($result as $record) {
      $this->replacement("node_field_data", $record->entity_id, "body_value", "delicious", "yummy");
    }
  }
  /**
   * @command batch_editing:showTitles
   */
  public function showTitles() {
    $database = \Drupal::database();
    $query = $database->query("SELECT title FROM node_field_data");
    $result = $query->fetchAll();
    var_dump($result);
  }

  /**
   * @command batch_editing:fixTitle
   */
  public function fixTitle() {
    // データベースに接続
    $connection = \Drupal::service("database");
    // テーブルnodeを読みに行き、nidとtypeを取得
    $result = $connection->query("SELECT nid, type FROM node")->fetchAll();
    foreach($result as $record) {
      echo("nid" . $record->nid . " is " . $record->type . "\n");
      if ($record->type == "recipe") {
        echo("hoge\n");
      } else {
        echo("piyo\n");
      }
    }
    //var_dump($result);
    //$query = $connection->update("node_field_data")->field("title")
  }

  private function replacement($table, $entity_id, $field, $old_value, $new_value) {
    // データベースに接続
    $connection = \Drupal::database();
    $connection->update($table)
      ->condition("entity_id", $entity_id, "=")
      ->expression($field, "REPLACE($field, :old_value, :new_value)", [
        ":old_value" => $old_value,
        ":new_value" => $new_value
      ])
      ->execute();
  }


  /**
   * Command description here.
   *
   * @param $arg1
   *   Argument description.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option option-name
   *   Description
   * @usage batch_editing-commandName foo
   *   Usage description
   *
   * @command batch_editing:commandName
   * @aliases foo
   */
  public function commandName($arg1, $options = ['option-name' => 'default']) {
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command batch_editing:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
}

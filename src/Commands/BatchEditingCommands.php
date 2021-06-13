<?php

namespace Drupal\batch_editing\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
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
    // データベースに接続
    $connection = \Drupal::database();
    // テーブル"node"を見に行き、nidとtypeを取得
    $nodes = $connection->query("SELECT nid, type FROM node")->fetchAll();

    // コンテンツ編集ルール1
    // ・node.type = article, pageに対し実行
    // ・フィールドbodyに対し文字列置換を行なう
    // ・delicious->yummy, https://www.drupal.org->https://WWW.DRUPAL.ORG
    try {
      foreach($nodes as $record) {
        if ($record->type != "recipe") {
          $this->replacement(
            "node__body", 
            "entity_id", 
            $record->nid, 
            "body_value", 
            "delicious", 
            "yummy"
          );
          $this->replacement(
            "node__body", 
            "entity_id", 
            $record->nid, 
            "body_value", 
            "https://www.drupal.org", 
            "https://WWW.DRUPAL.ORG"
          );
        }
      }
    } catch (Exception $error) {
      echo($error->getMessage());
    } finally {
      echo("編集ルール1おわり" . PHP_EOL);
    }

    // コンテンツ編集ルール2
    // ・node.type = pageに対してのみ実行
    // ・フィールドtitleに対し文字列置換を行なう
    // ・s/Umami/this site/g
    try {
      foreach($nodes as $record) {
        if ($record->type == "page") {
          $this->replacement(
            "node_field_data",
            "nid", 
            $record->nid, 
            "title", 
            "Umami", 
            "this site"
          );
        }
      }
    } catch (Exception $error) {
      echo($error->getMessage());
    } finally {
      echo("編集ルール2おわり" . PHP_EOL);
    }

    // コンテンツ編集ルール3
    // ・node.type = recipeに対してのみ実行
    // ・フィールドRecipeInstructionに対し文字列置換
    // ・s/minutes/mins/g
    try {
      foreach($nodes as $record) {
        if ($record->type == "recipe") {
          $this->replacement(
            "node__field_recipe_instruction",
            "entity_id",
            $record->nid,
            "field_recipe_instruction_value",
            "minutes",
            "mins"
          );
        }
      }
    } catch(Exception $error) {
      echo($error->getMessage());
    } finally {
      echo("編集ルール3おわり" . PHP_EOL);
    }

    // コンテンツ編集ルール4
    // ・node.type = article,pageに対して実行
    // ・フィールド"title"に対し文字列置換
    // ・s/delicious/yummy/g
    try {
      foreach($nodes as $record) {
        if ($record->type != "recipe") {
          $this->replacement(
            "node__body", 
            "entity_id", 
            $record->nid, 
            "body_value", 
            "delicious", 
            "yummy"
          );
        }
      }
    } catch(Exception $error) {
      echo($error->getMessage());
    } finally {
      echo("編集ルール4おわり" . PHP_EOL);
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

  private function replacement($table, $condition_col, $condition_val, $field, $old_value, $new_value) {
    // データベースに接続
    $connection = \Drupal::database();
    $connection->update($table)
      ->condition($condition_col, $condition_val, "=")
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

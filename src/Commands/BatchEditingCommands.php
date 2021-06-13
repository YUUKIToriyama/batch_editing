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
   * 任意のテーブル、任意のコラムに関して文字列置換を行なうことができます
   * @param $table 
   */
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
    $count = 0;
    try {
      foreach($nodes as $record) {
        if ($record->type != "recipe") {
          $this->replacement(
            "node_revision__body", 
            "entity_id", 
            $record->nid, 
            "body_value", 
            "delicious", 
            "yummy"
          );
          $this->replacement(
            "node_revision__body", 
            "entity_id", 
            $record->nid, 
            "body_value", 
            "https://www.drupal.org", 
            "https://WWW.DRUPAL.ORG"
          );
          // 処理が終了したらカウントを1Upする
          $count++;
        }
      }
    } catch (Exception $error) {
      echo($error->getMessage());
    } finally {
      echo("編集ルール1:" . $count . "件処理しました" . PHP_EOL);
    }

    // コンテンツ編集ルール2
    // ・node.type = pageに対してのみ実行
    // ・フィールドtitleに対し文字列置換を行なう
    // ・s/Umami/this site/g
    $count = 0;
    try {
      foreach($nodes as $record) {
        if ($record->type == "page") {
          $this->replacement(
            "node_field_revision",
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
      echo("編集ルール2おわり" . $count . "件処理しました" . PHP_EOL);
    }

    // コンテンツ編集ルール3
    // ・node.type = recipeに対してのみ実行
    // ・フィールドRecipeInstructionに対し文字列置換
    // ・s/minutes/mins/g
    $count = 0;
    try {
      foreach($nodes as $record) {
        if ($record->type == "recipe") {
          $this->replacement(
            "node_revision__field_recipe_instruction",
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
      echo("編集ルール3おわり" . $count . "件処理しました" . PHP_EOL);
    }

    // コンテンツ編集ルール4
    // ・node.type = article,pageに対して実行
    // ・フィールド"title"に対し文字列置換
    // ・s/delicious/yummy/g
    $count = 0;
    try {
      foreach($nodes as $record) {
        if ($record->type != "recipe") {
          $this->replacement(
            "node_revision__body", 
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
      echo("編集ルール4おわり" . $count . "件処理しました" . PHP_EOL);
    }
  }
}

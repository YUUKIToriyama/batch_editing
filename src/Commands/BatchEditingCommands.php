<?php

namespace Drupal\batch_editing\Commands;

use Drush\Commands\DrushCommands;
use Exception;

class BatchEditingCommands extends DrushCommands
{
  /**
   * 任意のテーブル、任意のコラムに関して文字列置換を行なうことができます
   * @param $table 置換を行ないたいテーブルを指定
   * @param $condition_col レコードを特定するための条件
   * @param $condition_val レコードを特定するための条件
   * @param $field 置換を行ないたいフィールドを指定
   * @param $old_value 置換を掛けたい文字列を指定 
   * @param $new_value 置換する文字列を指定
   */
  private function replacement($table, $condition_col, $condition_val, $field, $old_value, $new_value)
  {
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
  public function batch_editing()
  {
    // データベースに接続
    $connection = \Drupal::database();
    // テーブル"node"を見に行き、nidとtypeを取得
    $nodes = $connection->query("SELECT nid, type FROM node")->fetchAll();

    // コンテンツ編集ルール1
    try {
      foreach ($nodes as $record) {
        // type = article, pageに対し実行
        if ($record->type != "recipe") {
          $this->replacement(
            "node_revision__body",
            "entity_id",
            $record->nid,
            "body_value",
            "delicious", "yummy" // delicious -> yummy
          );
          $this->replacement(
            "node_revision__body",
            "entity_id",
            $record->nid,
            "body_value", // フィールドbodyに対し文字列置換を行なう
            "https://www.drupal.org", "https://WWW.DRUPAL.ORG" // https://www.drupal.org -> https://WWW.DRUPAL.ORG
          );
        }
      }
    } catch (Exception $error) {
      echo ($error->getMessage());
    } finally {
      echo ("編集ルール1: 終了" . PHP_EOL);
    }

    // コンテンツ編集ルール2
    try {
      foreach ($nodes as $record) {
        // node.type = pageに対してのみ実行
        if ($record->type == "page") {
          $this->replacement(
            "node_field_revision",
            "nid",
            $record->nid,
            "title", // フィールドtitleに対し文字列置換を行なう
            "Umami", "this site" // s/Umami/this site/g
          );
        }
      }
    } catch (Exception $error) {
      echo ($error->getMessage());
    } finally {
      echo ("編集ルール2: 終了" . PHP_EOL);
    }

    // コンテンツ編集ルール3
    try {
      foreach ($nodes as $record) {
        // type = recipeに対してのみ実行
        if ($record->type == "recipe") {
          $this->replacement(
            "node_revision__field_recipe_instruction",
            "entity_id",
            $record->nid,
            "field_recipe_instruction_value", // フィールドRecipeInstructionに対し文字列置換
            "minutes", "mins" // s/minutes/mins/g
          );
        }
      }
    } catch (Exception $error) {
      echo ($error->getMessage());
    } finally {
      echo ("編集ルール3: 終了" . PHP_EOL);
    }

    // コンテンツ編集ルール4
    try {
      foreach ($nodes as $record) {
        // type = article,pageに対して実行
        if ($record->type != "recipe") {
          $this->replacement(
            "node_field_revision",
            "nid",
            $record->nid,
            "title", // フィールド"title"に対し文字列置換
            "delicious", "yummy" // s/delicious/yummy/g
          );
        }
      }
    } catch (Exception $error) {
      echo ($error->getMessage());
    } finally {
      echo ("編集ルール4: 終了" . PHP_EOL);
    }
  }

  /**
   * @command batch_editing:credit
   */
  public function credit() {
    echo ("@ 2021 YUUKIToriyama All Rights Reserved." . PHP_EOL);
  }
}

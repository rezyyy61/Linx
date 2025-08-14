import js from "@eslint/js";
import vue from "eslint-plugin-vue";
import tsParser from "@typescript-eslint/parser";
import ts from "@typescript-eslint/eslint-plugin";
import vueParser from "vue-eslint-parser";

export default [
  js.configs.recommended,

  ...vue.configs["flat/recommended"],

  {
    files: ["**/*.ts", "**/*.tsx"],
    languageOptions: {
      parser: tsParser,
      parserOptions: { ecmaVersion: "latest", sourceType: "module" }
    },
    plugins: { "@typescript-eslint": ts },
    rules: {}
  },

  {
    files: ["**/*.vue"],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tsParser,
        ecmaVersion: "latest",
        sourceType: "module"
      }
    },
    plugins: { vue, "@typescript-eslint": ts },
    rules: {}
  }
];

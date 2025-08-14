import js from "@eslint/js";
import vue from "eslint-plugin-vue";
import tsParser from "@typescript-eslint/parser";
import ts from "@typescript-eslint/eslint-plugin";

export default [
  ...vue.configs["flat/essential"],
  js.configs.recommended,
  {
    files: ["**/*.{ts,vue,js}"],
    languageOptions: {
      parser: tsParser,
      parserOptions: { ecmaVersion: "latest", sourceType: "module" }
    },
    plugins: { "@typescript-eslint": ts },
    rules: {}
  }
];

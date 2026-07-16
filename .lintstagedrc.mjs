export default {
  'yii2-adapter/**/*.php': [
    './yii2-adapter/vendor/bin/ecs check --config ./yii2-adapter/ecs.php --ansi --fix',
  ],
  '!(yii2-adapter)/**/*.php': ['./vendor/bin/rector', './vendor/bin/pint'],
  'yii2-adapter/**/*.scss': [
    'stylelint --fix --allow-empty-input -c ./yii2-adapter/.stylelintrc.json',
  ],
  '!(yii2-adapter)/**/*.scss': ['stylelint --fix --allow-empty-input'],
  '!(yii2-adapter)/**/*.{html,json,css,scss}': 'prettier --ignore-unknown --write',
  // eslint takes the staged files; vue-tsc must run project-wide with no file
  // args, otherwise it ignores tsconfig.json and throws TS5112.
  'resources/js/**/*.{ts,vue}': (files) => [
    `eslint ${files.join(' ')}`,
    'vue-tsc --noEmit',
  ],
};

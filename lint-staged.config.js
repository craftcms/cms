module.exports = {
    '**/*.php': [
        './vendor/bin/ecs check --ansi --fix',
        './vendor/bin/phpstan --fix',
    ],
};

import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { parseArgs } from 'node:util';
import { createClient, isFlexaError } from '@flexacms/client';
import { generateTypes } from './generate';

const HELP = `Usage: flexa generate [options]

Generate TypeScript types for your Flexa workspace's content models.

Options:
  --url <url>      Flexa URL (default: $FLEXA_URL)
  --key <key>      API key with the Read ability (default: $FLEXA_API_KEY)
  --out <file>     Output file (default: flexa.d.ts)
  -h, --help       Show this help
`;

async function main(argv: string[]): Promise<number> {
    const { positionals, values } = parseArgs({
        args: argv,
        allowPositionals: true,
        options: {
            url: { type: 'string' },
            key: { type: 'string' },
            out: { type: 'string', default: 'flexa.d.ts' },
            help: { type: 'boolean', short: 'h' },
        },
    });

    if (values.help || positionals[0] !== 'generate') {
        process.stdout.write(HELP);

        return values.help ? 0 : 1;
    }

    const url = values.url ?? process.env.FLEXA_URL;
    const apiKey = values.key ?? process.env.FLEXA_API_KEY;

    if (!url || !apiKey) {
        process.stderr.write(
            'Missing --url/--key (or the FLEXA_URL and FLEXA_API_KEY environment variables).\n',
        );

        return 1;
    }

    const client = createClient({ url, apiKey });
    const entities = await client.entities();
    const schemas = await Promise.all(
        entities.map((entity) => client.entity(entity.slug).schema()),
    );
    const output = resolve(process.cwd(), values.out ?? 'flexa.d.ts');

    await mkdir(dirname(output), { recursive: true });
    await writeFile(output, generateTypes(schemas));

    process.stdout.write(
        `Generated types for ${schemas.length} content model(s) in ${output}\n`,
    );

    return 0;
}

main(process.argv.slice(2)).then(
    (code) => process.exit(code),
    (error: unknown) => {
        process.stderr.write(
            `${isFlexaError(error) ? `Flexa API error (${error.status}): ` : ''}${error instanceof Error ? error.message : String(error)}\n`,
        );
        process.exit(1);
    },
);

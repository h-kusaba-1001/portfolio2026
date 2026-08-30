/*
    ビルド時プリレンダ（ADR-020）用のエントリ `ssr.tsx` は Node で動くため、
    `process` と `node:fs` を使う。

    tsconfig の `types` は `vite/client` だけに絞っており、
    ブラウザ向けのコードに Node の型が紛れ込まないようにしている。
    そのため `@types/node` を入れずに、**この 1 ファイルが使う分だけ**を宣言する。

    依存を増やさないための措置。使う API が増えたら、
    ここに足すか `@types/node` の導入を検討する。
*/

declare module 'node:fs' {
    export function readFileSync(path: string, encoding: 'utf-8'): string;
}

declare const process: {
    readonly argv: readonly string[];
    readonly stdout: { write(text: string): void };
    readonly stderr: { write(text: string): void };
    exit(code: number): never;
};

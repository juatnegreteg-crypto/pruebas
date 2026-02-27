const DEFAULT_IGNORED_ATTRIBUTES = new Set([
    'class',
    'id',
    'style',
    'type',
    'name',
    'value',
    'for',
    'href',
    'src',
    'key',
    'ref',
    'role',
    'tabindex',
    'rel',
    'target',
    'method',
    'action',
    'autocomplete',
    'maxlength',
    'min',
    'max',
    'step',
    'size',
    'rows',
    'cols',
    'checked',
    'disabled',
    'readonly',
    'required',
    'multiple',
    'aria-hidden',
]);

const VALUE_HAS_TEXT = /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]/u;

function isIgnorableAttribute(name) {
    if (!name) {
        return true;
    }

    if (DEFAULT_IGNORED_ATTRIBUTES.has(name)) {
        return true;
    }

    return name.startsWith('data-');
}

function hasExemptComment(context, node) {
    if (!node.loc) {
        return false;
    }

    const sourceCode = context.getSourceCode();
    const targetLine = node.loc.start.line - 1;
    if (targetLine < 1) {
        return false;
    }

    return sourceCode.getAllComments().some((comment) => {
        if (!comment.loc || comment.loc.end.line !== targetLine) {
            return false;
        }

        return comment.value.trim() === 'i18n-exempt-next-line';
    });
}

export default {
    meta: {
        type: 'problem',
        docs: {
            description: 'disallow hardcoded UI strings in Vue templates',
        },
        schema: [],
        messages: {
            hardcodedText: 'Replace hardcoded UI text with i18n key.',
            hardcodedAttribute: 'Replace hardcoded attribute value with i18n key.',
            exemptNotAllowed: 'i18n exemptions are not allowed in strict mode.',
        },
    },
    create(context) {
        const strict =
            process.env.I18N_STRICT === 'true' ||
            process.env.NODE_ENV === 'production';

        const reportOrSkip = (node, messageId) => {
            const hasExempt = hasExemptComment(context, node);

            if (hasExempt && !strict) {
                return;
            }

            if (hasExempt && strict) {
                context.report({ node, messageId: 'exemptNotAllowed' });
                return;
            }

            context.report({ node, messageId });
        };

        const { parserServices } = context;

        if (!parserServices?.defineTemplateBodyVisitor) {
            return {};
        }

        return parserServices.defineTemplateBodyVisitor(
            {
                VText(node) {
                    const value = node.value ?? '';

                    if (!VALUE_HAS_TEXT.test(value)) {
                        return;
                    }

                    reportOrSkip(node, 'hardcodedText');
                },
                VAttribute(node) {
                    if (!node.value || node.value.type !== 'VLiteral') {
                        return;
                    }

                    const attributeName = node.key?.name;
                    if (isIgnorableAttribute(attributeName)) {
                        return;
                    }

                    const value = String(node.value.value ?? '');
                    if (!VALUE_HAS_TEXT.test(value)) {
                        return;
                    }

                    reportOrSkip(node, 'hardcodedAttribute');
                },
            },
            {
                VExpressionContainer() {
                    return;
                },
            },
        );
    },
};

export default {
    inputtext: {
        root: ({ props, context }) => ({
            class: [
                'font-sans leading-none',
                'm-0 p-3',
                'w-full',
                'rounded-xl',
                'border-slate-200',
                'bg-slate-50/50',
                'text-slate-800',
                'placeholder:text-slate-400',
                'focus:ring-2 focus:ring-primary-500/20 focus:bg-white',
                'transition-colors duration-200',
                {
                    'border-red-500': props.invalid,
                    'opacity-60 select-none pointer-events-none cursor-default': context.disabled
                }
            ]
        })
    },
    inputnumber: {
        input: {
            root: 'font-sans leading-none w-full p-3 border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-colors duration-200 rounded-xl'
        }
    },
    dropdown: {
        root: 'w-full rounded-xl border border-slate-200 bg-slate-50/50 transition-colors duration-200',
        input: 'p-3 font-sans text-slate-800',
        trigger: 'bg-transparent text-slate-500 w-12 rounded-r-xl',
        panel: 'bg-white rounded-xl shadow-lg border-0 mt-2',
        item: 'p-3 font-sans text-slate-700 hover:bg-slate-100'
    },
    calendar: {
        input: 'font-sans leading-none w-full p-3 border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-colors duration-200 rounded-xl',
        panel: 'bg-white rounded-xl shadow-lg border-0 mt-2 p-4',
        header: 'p-0 mb-4',
        title: 'text-lg font-black text-slate-800',
        dayLabel: 'text-slate-400 font-bold',
        day: 'p-0',
        dayCell: 'p-1',
    },
    textarea: {
        root: 'font-sans leading-none w-full p-3 border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-colors duration-200 rounded-xl'
    }
    // Ajoutez d'autres composants ici (MultiSelect, etc.)
}

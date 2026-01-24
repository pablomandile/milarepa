export function useDataTableStyles() {
    // Este composable retorna una referencia que indica que los estilos de DataTable están aplicados
    // Los estilos se aplican a través de <style scoped> con :deep()
    
    return {
        // Placeholder para futuras expansiones
        headerBackgroundColor: 'rgb(165, 180, 252)',
        headerTextColor: 'rgb(255, 255, 255)',
    };
}
